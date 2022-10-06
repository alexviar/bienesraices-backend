<?php

namespace App\Listeners;

use App\Events\PagoCuotaCreated;
use App\Events\ReservaCreated;
use App\Events\TransaccionRegistrada;
use App\Events\VentaCreated;
use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use Exception;

class TransaccionSubscriber {

    public function handleReservaCreated(ReservaCreated $event){
        $transaccion = Transaccion::firstOrCreate([
            "user_id" => $event->userId,
            "fecha" => $event->reserva->fecha,
            "estado" => 0
        ]);
        $transaccion->detalles()->create([
            "moneda" => $event->reserva->getAttributes()["moneda"],
            "importe" => $event->reserva->getAttributes()["importe"],
            "referencia" => $event->reserva->getReferencia(),
            "transactable_id" => $event->reserva->getMorphKey(),
            "transactable_type" => $event->reserva->getMorphClass()
        ]);
    }

    public function handleVentaCreated(VentaCreated $event){
        $transaccion = Transaccion::firstOrCreate([
            "user_id" => $event->userId,
            "fecha" => $event->venta->fecha,
            "estado" => 0
        ]);

        if($event->venta->tipo == 2){
            $transactable = $event->venta->credito;
            $importe = $transactable->cuota_inicial;
        }
        else if($event->venta->tipo == 1){
            $transactable = $event->venta;
            $importe = $transactable->importe;
        }

        $transactable = $event->venta->credito ?? $event->venta;
        $morphKeyName = $transactable->getMorphKeyName();
        $transaccion->detalles()->create([
            "moneda" => $importe->currency->code,
            "importe" => (string) $importe->amount,
            "referencia" => $transactable->getReferencia(),
            "transactable_id" => $transactable->{$morphKeyName},
            "transactable_type" => $transactable->getMorphClass()
        ]);
    }

    public function handlePagoCuotaCreated(PagoCuotaCreated $event){
        $transaccion = Transaccion::firstOrCreate([
            "user_id" => $event->userId,
            "fecha" => $event->pago->fecha,
            "estado" => 0
        ]);
        $transaccion->detalles()->create([
            "moneda" => $event->pago->getAttributes()["moneda"],
            "importe" => $event->pago->getAttributes()["importe"],
            "referencia" => $event->pago->cuota->getReferencia(),
            "transactable_id" => $event->pago->getMorphKey(),
            "transactable_type" => $event->pago->getMorphClass()
        ]);
    }

    function actualizarCuota(Cuota $pagable, $fecha, $importe){
        $total = $pagable->total;
        $saldo = $pagable->saldo;
        $pagable->pagos()->create([
            "fecha" => $fecha,
            "moneda" => $pagable->getCurrency()->code,
            "importe" => $importe
        ]);
        do{
            $pagable->refresh();
            $pagable->projectTo($fecha);
            if(!$pagable->pendiente){
                throw new Exception("Solo puede pagar cuotas vencidas o en curso.");
            }
            if($pagable->total->amount->isNegative()){
                throw new Exception("El pago excede el importe a pagar.");
            }
            
            $pagable->recalcularSaldo();
            $updated = Cuota::where("id", $pagable->id)
                ->where("updated_at", $pagable->updated_at)
                ->update([
                    "saldo" => $pagable->saldo->amount,
                    "total_pagos" => $pagable->total_pagos->plus($importe)->amount
                ]);
        } while(!$updated);
    }

    function actualizarPagable(DetalleTransaccion $detalleTransacccion){
        $pagable = $detalleTransacccion->pagable;
        $importe = $detalleTransacccion->getAttributes()["importe"];
        do{
            $pagable->refresh();
            if($pagable->saldo->amount->isLessThan($importe)){
                throw new Exception("El pago excede el importe a pagar");
            }
            $updated = $detalleTransacccion->pagable_type::where("id", $pagable->id)
                ->where("updated_at", $pagable->updated_at)
                ->update([
                    "saldo" => $pagable->saldo->minus($importe)->amount
                ]);
        } while(!$updated);
    }



    public function handleTransaccionRegistrada(TransaccionRegistrada $event){
        foreach($event->transaccion->detalles as $detalle){
            if($detalle->pagable_type == Cuota::class){
                $this->actualizarCuota($detalle->pagable()->whereHas("credito", function($query){
                    $query->where("estado", 1);
                })->first(), $event->transaccion->fecha, $detalle->getAttributes()["importe"]);
            }
            else{
                $this->actualizarPagable($detalle);
            }
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        // $events->listen(
        //     PagoCuotaCreated::class,
        //     [self::class, "handlePagoCuotaCreated"]
        // );
        // $events->listen(
        //     VentaCreated::class,
        //     [self::class, "handleVentaCreated"]
        // );

        $events->listen(
            TransaccionRegistrada::class,
            [self::class, "handleTransaccionRegistrada"]
        );
    }
}