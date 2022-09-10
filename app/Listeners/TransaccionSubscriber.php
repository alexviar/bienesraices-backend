<?php

namespace App\Listeners;

use App\Events\PagoCuotaCreated;
use App\Models\Transaccion;

class TransaccionSubscriber {

    public function handlePagoCuotaCreated(PagoCuotaCreated $event){
        // $transaccion = Transaccion::where("user_id", $event->userId)
        //     ->where("fecha", $event->pago->fecha)
        //     ->where("estado", 0)
        //     ->first();
        $transaccion = Transaccion::firstOrCreate([
            "user_id" => $event->userId,
            "fecha" => $event->pago->fecha,
            "estado" => 0
        ]);
        $transaccion->detalles()->create([
            "moneda" => $event->pago->getAttributes()["moneda"],
            "importe" => $event->pago->getAttributes()["importe"],
            "referencia" => $event->pago->cuota->getReferencia(),
            "transactable_id" => $event->pago->cuota->transactable_id,
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
    }
}