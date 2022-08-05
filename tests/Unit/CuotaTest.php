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
    $cuota->saldo = "78.93";

    $fecha = Carbon::createFromFormat("Y-m-d", "2022-11-01")->startOfDay();
    $this->assertTrue($cuota->calcularPago($fecha)->isEqualTo("78.93"));
    
    $fecha->addDay();
    /** @var \Mockery\MockInterface $mock */
    $mock = \Mockery::mock(UfvRepositoryInterface::class);
    Container::getInstance()->instance(UfvRepositoryInterface::class, $mock);
    $mock->shouldReceive("findByDate")->andReturn(BigDecimal::one());

    $pago = $cuota->calcularPago($fecha);
    $pago = $pago->toScale(2, RoundingMode::HALF_UP);
    $this->assertTrue($pago->isEqualTo("78.94"));

    $fecha->addDays(89);
    $pago = $cuota->calcularPago($fecha);
    $pago = $pago->toScale(2, RoundingMode::HALF_UP);
    $this->assertTrue($pago->isEqualTo("79.52"));

    $fecha->addDays(275);
    $pago = $cuota->calcularPago($fecha);
    $pago = $pago->toScale(2, RoundingMode::HALF_UP);
    $this->assertTrue($pago->isEqualTo("81.33"));
    
});