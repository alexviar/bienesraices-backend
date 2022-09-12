<?php

use App\Events\PagoCuotaCreated;
use App\Events\ReservaCreated;
use App\Events\VentaCreated;
use App\Listeners\TransaccionSubscriber;
use App\Models\Credito;
use App\Models\DetalleTransaccion;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;


test("registra transaccion por reservas", function($dataset){
    $reserva = $dataset["reserva"];

    $event = new ReservaCreated($reserva, User::find(1)->id);
    $subscriber = new TransaccionSubscriber();
    $subscriber->handleReservaCreated($event);

    $detalleTransaccion = DetalleTransaccion::latest("id")->first();
    $this->assertEquals([
        "moneda" => $reserva->getAttributes()["moneda"],
        "importe" => $reserva->getAttributes()["importe"],
        "referencia" => $reserva->getReferencia(),
        "transactable_type" => $reserva->getMorphClass(),
        "transactable_id" => $reserva->getMorphKey()
    ], [
        "moneda" => $detalleTransaccion->getAttributes()["moneda"],
        "importe" => $detalleTransaccion->getAttributes()["importe"],
        "referencia" => $detalleTransaccion->referencia,
        "transactable_type" => $detalleTransaccion->transactable_type,
        "transactable_id" => $detalleTransaccion->transactable_id
    ]);
})->with([
    function(){
        $reserva = Reserva::factory()->create();
        return [
            "reserva" => $reserva->refresh(),
        ];
    }
]);

test("registra transaccion por venta", function($dataset){
    $venta = $dataset["venta"];

    $event = new VentaCreated($venta, User::find(1)->id);
    $subscriber = new TransaccionSubscriber();
    $subscriber->handleVentaCreated($event);

    assertTransaccionPorVenta($venta);
})->with([
    function(){
        $venta = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.9600",
        ])->contado()->withoutReserva()->create();
        return [
            "venta" => $venta,
        ];
    },
    function(){
        $venta = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.9600",
        ])->credito()->has(Credito::factory([
            "cuota_inicial" => "500.0000"
        ]))->withoutReserva()->create();
        return [
            "venta" => $venta,
        ];
    }
]);

test("registra transaccion por pago de cuotas", function($dataset){
    $pago = $dataset["pago"];

    $event = new PagoCuotaCreated($pago, User::find(1)->id);
    $subscriber = new TransaccionSubscriber();
    $subscriber->handlePagoCuotaCreated($event);

    $detalleTransaccion = DetalleTransaccion::latest("id")->first();
    $this->assertEquals([
        "moneda" => $pago->getAttributes()["moneda"],
        "importe" => $pago->getAttributes()["importe"],
        "referencia" => $pago->cuota->getReferencia(),
        "transactable_type" => $pago->getMorphClass(),
        "transactable_id" => $pago->getMorphKey()
    ], [
        "moneda" => $detalleTransaccion->getAttributes()["moneda"],
        "importe" => $detalleTransaccion->getAttributes()["importe"],
        "referencia" => $detalleTransaccion->referencia,
        "transactable_type" => $detalleTransaccion->transactable_type,
        "transactable_id" => $detalleTransaccion->transactable_id
    ]);
})->with([
    function(){
        $venta = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.9600",
        ])->credito()->has(Credito::factory([
            "cuota_inicial" => "500.0000"
        ]))->withoutReserva()->create();
        $venta->credito->build();
        $cuota =  $venta->credito->cuotas->first();
        return [
            "pago" => $cuota->pagos()->create([
                "fecha" => $cuota->vencimiento->format("Y-m-d"),
                "moneda" => $cuota->getCurrency()->code,
                "importe" => "100"
            ])->refresh(),
        ];
    }
]);