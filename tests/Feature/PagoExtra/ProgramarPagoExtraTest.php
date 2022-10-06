<?php

use App\Models\Credito;
use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\PagoExtra;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @return TestResponse
 */
function makeRequest(?Credito &$credito, ?array &$body = [])
{

    if(!$credito){
        $credito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1,
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "moneda" => "USD",
            "importe" => "500",
        ])->credito("10030.96"), "creditable")->create();
        $credito->build();
    }

    $body = ($body ?? []) + [
        "importe" => "1000.00",
        "periodo" => 5,
        "tipo_ajuste" => 1 //Prorrateo
    ];

    $response = test()->actingAs(User::find(1))->postJson("/api/creditos/$credito->codigo/pagos-extras", $body);
    
    return $response;
}

// // test("decimal", function(){
// //     $a = BigDecimal::of("0.1")->multipliedBy(30)->dividedBy(360, 20, RoundingMode::HALF_UP);
// //     $b = BigDecimal::of("10351.8");
// //     $c = $b->multipliedBy($a);
// //     dd((string)$c);
// //     expect($c->isEqualTo("86.265"))->toBeTrue();
// //     // expect(c.toDecimalPlaces(2).comparedTo("86.27")).toBe(0)
// // });

it('registra un pago extra', function () {
    /** @var TestCase $this */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });
    $response = makeRequest($credito, $body);
    $response->assertCreated();
    $credito = Credito::find($response->json("id"));

    $this->assertCount(1, $credito->pagosExtras);
    $this->assertSame($body["importe"], (string) $credito->pagosExtras[0]->importe->amount->toScale(2, RoundingMode::HALF_UP));
    $this->assertSame(5, $credito->pagosExtras[0]->periodo);
    $this->assertSame(1, $credito->pagosExtras[0]->tipo_ajuste);
});

it('registra un segundo pago extra', function () {
    /** @var TestCase $this */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });
    $response = makeRequest($credito, $body);
    $response->assertCreated();
    $credito = Credito::find($response->json("id"));
    $response = makeRequest($credito, $body);
    $response->assertCreated();
    $credito = Credito::find($response->json("id"));

    $this->assertCount(2, $credito->pagosExtras);
    $this->assertSame($body["importe"], (string) $credito->pagosExtras[1]->importe->amount->toScale(2, RoundingMode::HALF_UP));
    $this->assertSame($body["periodo"], $credito->pagosExtras[1]->periodo);
    $this->assertSame($body["tipo_ajuste"], $credito->pagosExtras[1]->tipo_ajuste);
});

test('Programar un segundo pago extra para un periodo anterior.', function () {
    /** @var TestCase $this */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });
    $body = ["periodo" => 14];
    $response = makeRequest($credito, $body);
    $credito = Credito::find($response->json("id"));
    $body = ["periodo" => 13];
    $response = makeRequest($credito, $body);
    $response->assertJsonValidationErrors([
        "periodo" => "No puede programar un pago extra en el periodo indicado."
    ]);
});

it('Genera un nuevo credito y anula el anterior.', function () {
    /** @var TestCase $this */
    $body = ["periodo" => 14];
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });
    $response = makeRequest($credito, $body);
    $nuevoCredito = Credito::find($response->json("id"));
    expect($nuevoCredito->id)->not->toBe($credito->id);
    expect($credito->fresh()->estado)->toBe(2);
    expect($nuevoCredito->estado)->toBe(1);
});

// it('Prohibe el acceso si el credito está anulado.', function () {
//     /** @var TestCase $this */
//     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
//         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
//     });
//     $body = ["periodo" => 14];
//     $response = makeRequest($credito, $body);
//     $body = ["periodo" => 15];
//     $response = makeRequest($credito, $body);
//     $response->assertForbidden();
// });

it('copia las referencias del credito anterior', function(){
    /** @var TestCase $this */
    $body = ["periodo" => 14];
    $credito = Credito::factory([
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1,
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "500",
    ])->credito("10030.96"), "creditable")->create();
    $credito->build();

    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });

    $transaccionCuotaInicial = DetalleTransaccion::factory()
    ->for(Transaccion::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "500",
    ]))->for($credito, "pagable")->create();

    $transaccionPagoCuota1 = DetalleTransaccion::factory()
    ->for(Transaccion::factory([
        "fecha" => "2022-03-05",
        "moneda" => "USD",
        "importe" => "100"
    ]))->for($credito->cuotas[0], "pagable")->create();
    $credito->cuotas[0]->pagos()->create([
        "fecha" => "2022-03-05",
        "moneda" => "USD",
        "importe" => "100"
    ]);

    PagoExtra::factory(["periodo" => 1])->for($credito)->create();
    PagoExtra::factory(["periodo" => 2])->for($credito)->create();
    PagoExtra::factory(["periodo" => 3])->for($credito)->create();

    $response = makeRequest($credito, $body);
    $nuevoCredito = Credito::find($response->json("id"));
    $this->assertTrue($transaccionCuotaInicial->is(DetalleTransaccion::whereMorphedTo("pagable", $nuevoCredito)->first()), "No se copió la referencia al pago de la cuota inicial.");
    $this->assertTrue($transaccionPagoCuota1->is(DetalleTransaccion::whereMorphedTo("pagable", $nuevoCredito->cuotas[0])->first()), "No se copió la referencia al pago de la primer cuota.");
    
    expect($nuevoCredito->cuotas->count())->toBe($credito->cuotas->count());
    expect($nuevoCredito->cuotas->pluck("id"))->not->toMatchArray($credito->cuotas->pluck("id"));
    expect($nuevoCredito->cuotas->pluck("pagable_id"))->toMatchArray($credito->cuotas->pluck("pagable_id"));

    //Si copiamos los pagos
    // expect($nuevoCredito->cuotas[0]->pagos[0]->getAttributes())->toMatchArray([
    //     "fecha" => "2022-03-05",
    //     "moneda" => "USD",
    //     "importe" => "100.0000"
    // ]);
    // expect($nuevoCredito->cuotas[0]->pagos[0]->id)->not->toBe($credito->cuotas[0]->pagos[0]->id);

    //Si no se copian los pagos porque un mismo registro sirve para todas las copias de una cuota
    expect($nuevoCredito->cuotas[0]->pagos[0]->id)->toBe($credito->cuotas[0]->pagos[0]->id);
    
    expect($nuevoCredito->pagosExtras->count()-1)->toBe($credito->pagosExtras->count());
    expect($nuevoCredito->pagosExtras->pluck(["importe", "tipo_ajuste", "periodo"]))
        ->toMatchArray($credito->pagosExtras->pluck(["importe", "tipo_ajuste", "periodo"]));
    expect($nuevoCredito->pagosExtras->pluck(["id"]))
        ->not->toMatchArray($credito->pagosExtras->pluck(["id"]));
});

require(__DIR__."/csv/dataset.php");

it("actualiza el plan de pagos", function($data){
    /** @var TestCase $this */
    $filename = $data["filename"];
    $credito = $data["credito"];
    $requests = $data["requests"];
    
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });

    foreach($requests as $body){
        $response = makeRequest($credito, $body);
        $response->assertCreated();
        $credito = Credito::find($response->json("id"));
    }

    $i = 0;
    foreach (read_csv($filename) as $row) {
        $cuota = $credito->cuotas[$i];
        $this->assertSame(array_combine([
            "numero",
            "vencimiento",
            "dias",
            "importe",
            "pago_extra",
            "interes",
            "amortizacion",
            "saldo_capital"
        ], $row) + [
            "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
        ], [
            "numero" => (string) $cuota->numero,
            "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
            "dias" => (string) $cuota->dias,
            "importe" => (string) $cuota->importe->amount->toScale(2, RoundingMode::HALF_UP),
            "pago_extra" => (string) $cuota->pago_extra->amount->toScale(2, RoundingMode::HALF_UP),
            "interes" => (string) $cuota->interes->amount->toScale(2, RoundingMode::HALF_UP),
            "amortizacion" => (string) $cuota->amortizacion->amount->toScale(2, RoundingMode::HALF_UP),
            "saldo_capital" => (string) $cuota->saldo_capital->amount->toScale(2, RoundingMode::HALF_UP),
            "saldo" => (string) $cuota->saldo->amount->toScale(2, RoundingMode::HALF_UP),
        ], "Cuota n.º $cuota->numero");
        $i++;
    }
})->with("planes_pago");