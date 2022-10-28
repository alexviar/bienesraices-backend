<?php

use App\Models\PlanPagosBuilder;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

it("Genera un plan de pagos", function($fecha, $prestamo, $interes, $plazo, $periodo, $dia, $expected){
    $builder = new PlanPagosBuilder(
        Carbon::createFromFormat("Y-m-d", $fecha)->startOfDay(),
        BigDecimal::of($prestamo),
        BigDecimal::of($interes),
        $plazo,
        $periodo,
        $dia
    );
    $cuotas = $builder->build();
    $i = 0;
    foreach (read_csv(__DIR__."/$expected") as $row) {
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
})->with([
    ["2022-02-28", "69815.48", "0.1", 48, 1, 1, "plan_pagos.csv"],
    ["2022-12-28", "10030.96", "0.1", 48, 1, 1, "plan_pagos_2.csv"],
    ["2022-12-01", "10030.96", "0.1", 48, 1, 1, "plan_pagos_3.csv"],
    ["2022-12-01", "10030.96", "0.1", 48, 1, 31, "plan_pagos_4.csv"],
]);