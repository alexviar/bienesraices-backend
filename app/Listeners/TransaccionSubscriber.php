<?php

namespace App\Listeners;

use App\Events\PagoCuotaCreated;
use App\Events\VentaCreated;
use App\Models\Transaccion;

class TransaccionSubscriber {

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
            "transactable_id" => $event->pago->cuota->getMorphKey(),
            "transactable_type" => $event->pago->cuota->getMorphClass()
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            PagoCuotaCreated::class,
            [self::class, "handlePagoCuotaCreated"]
        );
        $events->listen(
            VentaCreated::class,
            [self::class, "handleVentaCreated"]
        );
    }
}