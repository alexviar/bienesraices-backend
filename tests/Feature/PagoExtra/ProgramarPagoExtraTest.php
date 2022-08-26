<?php

use App\Models\Credito;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
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
            "importe" => "10530.96",
        ])->credito(), "creditable")->create();
        $credito->build();
    }

    $body = ($body ?? []) + [
        "importe" => "1000.00",
        "periodo" => 5,
        "tipo_ajuste" => 1 //Prorrateo
    ];

    $response = test()->actingAs(User::find(1))->postJson("/api/creditos/$credito->id/pagos-extras", $body);
    
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
    $response = makeRequest($credito, $body);
    $response->assertCreated();
    $credito = Credito::find($response->json("id"));


    $this->assertCount(1, $credito->pagosExtras);
    $this->assertSame($body["importe"], $credito->pagosExtras[0]->getAttributes()["importe"]);
    $this->assertSame(5, $credito->pagosExtras[0]->periodo);
    $this->assertSame(1, $credito->pagosExtras[0]->tipo_ajuste);
});

it('registra un segundo pago extra', function () {
    /** @var TestCase $this */
    $response = makeRequest($credito, $body);
    $response->assertCreated();
    $credito = Credito::find($response->json("id"));
    $response = makeRequest($credito, $body);
    $response->assertCreated();
    $credito = Credito::find($response->json("id"));

    $this->assertCount(2, $credito->pagosExtras);
    $this->assertSame($body["importe"], $credito->pagosExtras[1]->getAttributes()["importe"]);
    $this->assertSame($body["periodo"], $credito->pagosExtras[1]->periodo);
    $this->assertSame($body["tipo_ajuste"], $credito->pagosExtras[1]->tipo_ajuste);
});

test('Programar un segundo pago extra para un periodo anterior.', function () {
    /** @var TestCase $this */
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
    $response = makeRequest($credito, $body);
    $nuevoCredito = Credito::find($response->json("id"));
    expect($nuevoCredito->id)->not->toBe($credito->id);
    expect($credito->fresh()->estado)->toBe(2);
    expect($nuevoCredito->estado)->toBe(1);
});

it('Prohibe el acceso si el credito está anulado.', function () {
    /** @var TestCase $this */
    $body = ["periodo" => 14];
    $response = makeRequest($credito, $body);
    $body = ["periodo" => 15];
    $response = makeRequest($credito, $body);
    $response->assertForbidden();
});

require(__DIR__."/csv/dataset.php");

it("actualiza el plan de pagos", function($data){
    /** @var TestCase $this */
    $filename = $data["filename"];
    $credito = $data["credito"];
    $requests = $data["requests"];

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
            "importe" => (string) $cuota->importe->amount,
            "pago_extra" => (string) $cuota->pago_extra->amount,
            "interes" => (string) $cuota->interes->amount,
            "amortizacion" => (string) $cuota->amortizacion->amount,
            "saldo_capital" => (string) $cuota->saldo_capital->amount,
            "saldo" => (string) $cuota->saldo->amount,
        ], "Cuota n.º $cuota->numero");
        $i++;
    }
})->with("planes_pago");