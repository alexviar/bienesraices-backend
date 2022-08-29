<?php

use App\Infrastructure\Repositories\UfvRepository;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\PlanPagosBuilder;
use App\Models\UFV;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;

it("Calcular pago actualizado", function(){
    $venta = new Venta();
    $venta->moneda = "USD";
    $credito = new Credito([
        "tasa_mora" => "0.03"
    ]);
    $credito->setRelation("creditable", $venta);
    $cuota = new Cuota();
    $cuota->setRelation("credito", $credito);
    $cuota->vencimiento = Carbon::createFromFormat("Y-m-d", "2022-11-01")->startOfDay();
    $cuota->importe = "78.93";
    $cuota->pago_extra = "0.00";
    $cuota->saldo = "78.93";

    $fecha = Carbon::createFromFormat("Y-m-d", "2022-11-01")->startOfDay();
    $cuota->projectTo($fecha);
    $this->assertSame("78.9300", (string) $cuota->total->amount);
    
    $fecha->addDay();
    /** @var \Mockery\MockInterface $mock */
    $mock = \Mockery::mock(UfvRepositoryInterface::class);
    Container::getInstance()->instance(UfvRepositoryInterface::class, $mock);
    $mock->shouldReceive("findByDate")->andReturn(BigDecimal::one());

    $cuota->projectTo($fecha);
    $this->assertSame("78.9400", (string) $cuota->total->amount);

    $fecha->addDays(89);
    $cuota->projectTo($fecha);
    $this->assertSame("79.5200", (string) $cuota->total->amount);

    $fecha->addDays(275);
    $cuota->projectTo($fecha);
    $this->assertSame("81.3300", (string)$cuota->total->amount);
    
});


// it("Calcular pago actualizado 2", function(){
//     $cuota = new Cuota([
//         "importe" => "684.05",
//         "saldo" => "684.05",
//         "saldo_capital" => "20134.86",
//         "total_pagos" => "0"
//     ]);

//     $cuota->projectTo(Carbon::createFromFormat("Y-m-d", "2022-07-13")->startOfDay());



// });