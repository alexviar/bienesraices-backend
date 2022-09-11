<?php

use App\Models\Credito;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;

/**
 * Comprueba que se haya registrado una transaccion por la venta al contado
 *
 * @return TestCase
 */
function assertTransaccionPorVenta(Venta $venta)
{
    $test = test();

    $venta->refresh();
    $detalleTransaccion = DetalleTransaccion::latest("id")->first();
    if($venta->tipo == 1) {
        $test->assertEquals([
            "moneda" => $venta->getAttributes()["moneda"],
            "importe" => $venta->getAttributes()["importe"],
            "referencia" => $venta->getReferencia(),
            "transactable_type" => $venta->getMorphClass(),
            "transactable_id" => $venta->{$venta->getMorphKeyName()}
        ], [
            "moneda" => $detalleTransaccion->getAttributes()["moneda"],
            "importe" => $detalleTransaccion->getAttributes()["importe"],
            "referencia" => $detalleTransaccion->referencia,
            "transactable_type" => $detalleTransaccion->transactable_type,
            "transactable_id" => $detalleTransaccion->transactable_id
        ]);
    }
    else {
        $credito = $venta->credito;
        $test->assertEquals([
            "moneda" => $venta->getAttributes()["moneda"],
            "importe" => $credito->getAttributes()["cuota_inicial"],
            "referencia" => $credito->getReferencia(),
            "transactable_type" => $credito->getMorphClass(),
            "transactable_id" => $credito->{$credito->getMorphKeyName()}
        ], [
            "moneda" => $detalleTransaccion->getAttributes()["moneda"],
            "importe" => $detalleTransaccion->getAttributes()["importe"],
            "referencia" => $detalleTransaccion->referencia,
            "transactable_type" => $detalleTransaccion->transactable_type,
            "transactable_id" => $detalleTransaccion->transactable_id
        ]);
    }
}