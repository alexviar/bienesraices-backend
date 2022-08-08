<?php

use App\Models\Credito;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

// test('Solo puede tener programado un pago extra a la vez.', function () {
//     /** @var TestCase $this */
//     $credito = Credito::factory([
//         "plazo" => 48,
//         "periodo_pago" => 1,
//         "dia_pago" => 1,
//     ])->for(Venta::factory([
//         "fecha" => "2022-02-28",
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->credito())->create();
//     $credito->build();

//     $response = $this->postJson("/api/creditos/$credito->id/pagos-extras");
//     $response->assertStatus(409);
//     $response->assertJson([
//         "message" => "Solo puede tener programado un pago extra a la vez."
//     ]);
// });

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

// it('registra un pago extra', function () {
//     /** @var TestCase $this */
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();


//     $this->assertCount(1, $credito->pagosExtras);
//     $this->assertSame($body["importe"], $credito->pagosExtras[0]->getAttributes()["importe"]);
//     $this->assertSame(5, $credito->pagosExtras[0]->periodo);
//     $this->assertSame(1, $credito->pagosExtras[0]->tipo_ajuste);
// });

// it('actualiza el plan de pagos', function () {
//     /** @var TestCase $this */
//     $body = ["importe" => "100.00"];
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_1.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ]);
//         $i++;
//     }
// });

// it('registra un segundo pago extra', function () {
//     /** @var TestCase $this */
//     $body = ["importe" => "100.00"];
//     makeRequest($credito, $body);
//     $body["importe"] = "1000";
//     $body["periodo"] = 14;
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_2.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ]);
//         $i++;
//     }
// });

// it('registrar un segundo pago extra en un periodo anterior', function () {
//     /** @var TestCase $this */
//     $body = ["importe" => "1000", "periodo" => 14];
//     makeRequest($credito, $body);
//     $body["importe"] = "100";
//     $body["periodo"] = 5;
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();
//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_2.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ], "Cuota $cuota->numero");
//         $i++;
//     }
// });

// it('registra un segundo pago extra en un mismo periodo', function () {
//     /** @var TestCase $this */
//     $body = ["importe" => "13.33", "periodo" => 5];
//     makeRequest($credito, $body);
//     $body["importe"] = "86.67";
//     $body["periodo"] = 5;
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_1.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ]);
//         $i++;
//     }
// });

// it('registrar un primer pago extra con prorrata y un segundo con solo intereses', function(){
//     /** @var TestCase $this */
//     $body = ["importe" => "100.00"];
//     makeRequest($credito, $body);
//     $body = [
//         "importe" => "1000.00",
//         "periodo" => 14,
//         "tipo_ajuste" => 3
//     ];
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_3.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         if (!$row[6]) $row[6] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ]);
//         $i++;
//     }
// });

// it('registrar un primer pago extra con prorrata y un segundo con solo intereses 2', function(){
//     /** @var TestCase $this */
//     $body = ["importe" => "100.00"];
//     makeRequest($credito, $body);
//     $body = [
//         "importe" => "400.00",
//         "periodo" => 46,
//         "tipo_ajuste" => 3
//     ];
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_4.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         if (!$row[6]) $row[6] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ]);
//         $i++;
//     }
// });


// it('registrar un primer pago extra con prorrata y un segundo con solo intereses (invertido)', function(){
//     /** @var TestCase $this */
//     $body = [
//         "importe" => "1000.00",
//         "periodo" => 14,
//         "tipo_ajuste" => 3
//     ];
//     makeRequest($credito, $body);
//     $body = ["importe" => "100.00"];
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_3.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         if (!$row[6]) $row[6] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ]);
//         $i++;
//     }
// });

// it('registrar un primer pago extra con prorrata y un segundo con solo intereses 2 (invertido)', function(){
//     /** @var TestCase $this */
//     $body = [
//         "importe" => "400.00",
//         "periodo" => 46,
//         "tipo_ajuste" => 3
//     ];
//     makeRequest($credito, $body);
//     $body = ["importe" => "100.00"];
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_4.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         if (!$row[6]) $row[6] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ]);
//         $i++;
//     }
// });

// it('ASLKDJFÑLASJDÑ', function(){
//     /** @var TestCase $this */
//     $body = [
//         "importe" => "100.00",
//         "tipo_ajuste" => 3
//     ];
//     makeRequest($credito, $body);
//     $body = [
//         "importe" => "1100.00",
//         "periodo" => 14,
//         "tipo_ajuste" => 3
//     ];
//     $response = makeRequest($credito, $body);
//     $response->assertCreated();

//     $credito->load("cuotas");

//     /** @var FilesystemAdapter $disk */
//     $disk = Storage::disk("tests");
//     $i = 0;
//     foreach (read_csv($disk->path("Feature/PagoExtra/csv/plan_pagos_5.csv")) as $row) {
//         $cuota = $credito->cuotas[$i];
//         if (!$row[4]) $row[4] = "0.00";
//         if (!$row[6]) $row[6] = "0.00";
//         $this->assertSame(array_combine([
//             "numero",
//             "vencimiento",
//             "dias",
//             "importe",
//             "pago_extra",
//             "interes",
//             "amortizacion",
//             "saldo_capital"
//         ], $row) + [
//             "saldo" => (string) BigDecimal::of($row[3])->plus($row[4])
//         ], [
//             "numero" => (string) $cuota->numero,
//             "vencimiento" => $cuota->vencimiento->format("Y-m-d"),
//             "dias" => (string) $cuota->dias,
//             "importe" => (string) $cuota->importe->amount,
//             "pago_extra" => (string) $cuota->pago_extra->amount,
//             "interes" => (string) $cuota->interes->amount,
//             "amortizacion" => (string) $cuota->amortizacion->amount,
//             "saldo_capital" => (string) $cuota->saldo_capital->amount,
//             "saldo" => (string) $cuota->saldo->amount,
//         ], "Cuota n.º $cuota->numero");
//         $i++;
//     }
// });

require(__DIR__."/csv/dataset.php");

it("actualiza el plan de pagos", function($data){
    /** @var TestCase $this */
    $filename = $data["filename"];
    $credito = $data["credito"];
    $requests = $data["requests"];

    foreach($requests as $body){
        $response = makeRequest($credito, $body);
    }
    $response->assertCreated();
    
    $credito->load("cuotas");

    $i = 0;
    foreach (read_csv($filename) as $row) {
        $cuota = $credito->cuotas[$i];
        if (!$row[3]) $row[3] = "0.00";
        if (!$row[4]) $row[4] = "0.00";
        if (!$row[5]) $row[5] = "0.00";
        if (!$row[6]) $row[6] = "0.00";
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