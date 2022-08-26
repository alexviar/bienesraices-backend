<?php

use App\Models\PlanPagosBuilder;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

it("Genera un plan de pagos", function(){
    $builder = new PlanPagosBuilder(
        Carbon::createFromFormat("Y-m-d", "2022-02-28")->startOfDay(),
        BigDecimal::of("69815.48"),
        BigDecimal::of("0.1"),
        48,
        1,
        1
    );
    $cuotas = $builder->build();
    $i = 0;
    foreach (read_csv(__DIR__."/plan_pagos.csv") as $row) {
        $cuota = $cuotas[$i];
        $this->assertSame(array_combine([
            "numero",
            "vencimiento",
            "dias",
            "importe",
            "interes",
            "amortizacion",
            "saldo_capital"
        ], $row), [
            "numero" => (string) $cuota["numero"],
            "vencimiento" => $cuota["vencimiento"]->format("Y-m-d"),
            "dias" => (string) $cuota["diasTranscurridos"],
            "importe" => (string) $cuota["pago"],
            "interes" => (string) $cuota["interes"],
            "amortizacion" => (string) $cuota["amortizacion"],
            "saldo_capital" => (string) $cuota["saldo"],
        ]);
        $i++;
    }
});