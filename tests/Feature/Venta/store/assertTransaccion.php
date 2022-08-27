<?php

use App\Models\Credito;
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
function assertTransaccionPorVentaAlContado($data, Venta $venta, $pagoEsperado)
{
    $test = test();

    $transacciones = Transaccion::with("detalles")->whereHas("detalles", function($query) use($venta){
        $query->whereHas("ventas", function($query) use($venta){
            $query->where("id", $venta->id);
        });
    })->get();

    $test->assertSame(1, $transacciones->count());
    $test->assertSame(2, $transacciones[0]->forma_pago);
    $test->assertSame($data["fecha"], $transacciones[0]->getAttributes()["fecha"]);
    $test->assertSame(Arr::get($data, "pago.moneda"), $transacciones[0]->getAttributes()["moneda"]);
    $test->assertTrue(BigDecimal::of($transacciones[0]->getAttributes()["importe"])->isEqualTo(Arr::get($data, "pago.monto")));
    
    $test->assertSame(1, $transacciones[0]->detalles->count());
    $test->assertSame($venta->getReferencia(), $transacciones[0]->detalles[0]->referencia);
    $test->assertSame($venta->moneda, $transacciones[0]->detalles[0]->moneda);
    
    $test->assertSame((string) BigDecimal::of($pagoEsperado)->toScale(4, RoundingMode::HALF_UP), (string) $transacciones[0]->detalles[0]->importe->amount);

    
    // $this->assertDatabaseCount("transacciones", 1);
    // $this->assertDatabaseCount("detalles_transaccion", 1);
    // $this->assertDatabaseCount("transactables", 1);
}


/**
 * Comprueba que se haya registrado una transaccion por la venta al credito
 *
 * @return TestCase
 */
function assertTransaccionPorVentaAlCredito($data, Credito $credito, $pagoEsperado)
{
    $test = test();

    $transacciones = Transaccion::with("detalles")->whereHas("detalles", function($query) use($credito){
        $query->whereHas("creditos", function($query) use($credito){
            $query->where("id", $credito->id);
        });
    })->get();

    $test->assertSame(1, $transacciones->count());
    $test->assertSame(2, $transacciones[0]->forma_pago);
    $test->assertSame($data["fecha"], $transacciones[0]->getAttributes()["fecha"]);
    $test->assertSame(Arr::get($data, "pago.moneda"), $transacciones[0]->getAttributes()["moneda"]);
    $test->assertTrue(BigDecimal::of($transacciones[0]->getAttributes()["importe"])->isEqualTo(Arr::get($data, "pago.monto")));
    
    $test->assertSame(1, $transacciones[0]->detalles->count());
    $test->assertSame($credito->getReferencia(), $transacciones[0]->detalles[0]->referencia);
    
    $test->assertSame((string) BigDecimal::of($pagoEsperado)->toScale(4, RoundingMode::HALF_UP), (string) $transacciones[0]->detalles[0]->importe->amount);

    
    // $this->assertDatabaseCount("transacciones", 1);
    // $this->assertDatabaseCount("detalles_transaccion", 1);
    // $this->assertDatabaseCount("transactables", 1);
}