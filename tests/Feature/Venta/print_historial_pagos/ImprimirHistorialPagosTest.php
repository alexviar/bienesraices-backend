<?php

use Illuminate\Support\Str;
use App\Http\Reports\Venta\HistorialPagos;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\DetalleTransaccion;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Proyecto;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

function comparePdf($generatedContent, $sampleContent){
    $generatedContent = Str::beforeLast($generatedContent, "endstream");
    $generatedContent = Str::afterLast($generatedContent, "stream");

    $sampleContent = Str::beforeLast($sampleContent, "endstream");
    $sampleContent = Str::afterLast($sampleContent, "stream");

    return $generatedContent == $sampleContent;
}

it("Genera un reporte del historial de pagos", function(){
    /** @var TestCase $this */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });
    
    $maxId = Cliente::max("id") ?? 0;
    DB::statement('ALTER TABLE clientes AUTO_INCREMENT=' . intval($maxId + 1) . ';');
    DB::statement('START TRANSACTION;');
    $report = new HistorialPagos();
    $venta = Venta::factory([
        "fecha" => "2022-07-28",
        "importe" => "500",
        "moneda" => "USD",
    ])->for(Cliente::factory([
        "nombre" => "JOAQUIN",
        "apellido_paterno" => "CHUMACERO",
        "apellido_materno" => "YUPANQUI",

    ]))->for(Lote::factory(["numero" => 2])->for(Manzana::factory(["numero"=>"100"])->for(Proyecto::factory([
        "nombre" => "OPORTUNIDAD IV"
    ]))))->credito("3100")->create();
    $credito = Credito::factory([
        "plazo" => "48",
        "dia_pago" => 1,
        "periodo_pago" => 1,
        "tasa_interes" => "0.1"
    ])->for($venta, "creditable")->create();
    $credito->build();

    $cuota1 = $credito->cuotas->where("numero", 1)->first();
    $cuota1->update(["saldo" => "0", "total_pagos" => "78.95"]);
    $cuota2 = $credito->cuotas->where("numero", 2)->first();
    $cuota2->update(["saldo" => "0", "total_pagos" => "78.93"]);

    $transaccion = Transaccion::factory([
        "fecha" => "2022/07/28",
        "importe" => "500",
        "moneda" => "USD"
    ])->create();
    $detailModel = new DetalleTransaccion();
    $detailModel->referencia = "Cuota inicial de la venta N.º 1";
    $detailModel->moneda = "USD";
    $detailModel->importe = "500";
    $detailModel->transactable()->associate($credito);
    $transaccion->detalles()->save($detailModel);

    $transaccion = Transaccion::factory([
        "fecha" => "2022/08/24",
        "importe" => "70",
        "moneda" => "USD"
    ])->create();
    $detailModel = new DetalleTransaccion();
    $detailModel->referencia = "Pago de la cuota 1 del crédito 1";
    $detailModel->moneda = "USD";
    $detailModel->importe = "70";
    $detailModel->transactable()->associate($cuota1->pagos()->create([
        "fecha" => "2022/08/24",
        "importe" => "70",
        "moneda" => "USD"
    ]));
    $transaccion->detalles()->save($detailModel);

    $transaccion = Transaccion::factory([
        "fecha" => "2022/09/30",
        "importe" => "87.88",
        "moneda" => "USD"
    ])->create();
    $detailModel = new DetalleTransaccion();
    $detailModel->referencia = "Pago de la cuota 1 del crédito 1";
    $detailModel->moneda = "USD";
    $detailModel->importe = "8.95";
    $detailModel->transactable()->associate($cuota1->pagos()->create([
        "fecha" => "2022/09/30",
        "importe" => "8.95",
        "moneda" => "USD"
    ]));
    $transaccion->detalles()->save($detailModel);
    $detailModel = new DetalleTransaccion();
    $detailModel->referencia = "Pago de la cuota 2 del crédito 1";
    $detailModel->moneda = "USD";
    $detailModel->importe = "78.93";
    $detailModel->transactable()->associate($cuota2->pagos()->create([
        "fecha" => "2022/09/30",
        "importe" => "78.93",
        "moneda" => "USD"
    ]));
    $transaccion->detalles()->save($detailModel);

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-09-30"));    
    $credito->cuotas->each->refresh();
    $pdf = $report->generate($credito->refresh());
    $pdf->save(__DIR__."/historial_pagos_sample_10.pdf");

    $generatedContent = $pdf->output();
    $sampleContent = file_get_contents(__DIR__."/historial_pagos_sample_1.pdf");
    $this->assertTrue(comparePdf($generatedContent, $sampleContent));

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-11-01")->startOfDay());
    $credito->cuotas->each->refresh();
    $pdf = $report->generate($credito);
    // $pdf->save(__DIR__."/historial_pagos_sample_20.pdf");
    $generatedContent = $pdf->output();
    $sampleContent = file_get_contents(__DIR__."/historial_pagos_sample_2.pdf");
    $this->assertTrue(comparePdf($generatedContent, $sampleContent));

    $this->travel(1)->day();
    $credito->cuotas->each->refresh();
    $pdf = $report->generate($credito);
    // $pdf->save(__DIR__."/historial_pagos_sample_3.pdf");
    $generatedContent = $pdf->output();
    $sampleContent = file_get_contents(__DIR__."/historial_pagos_sample_3.pdf");
    $this->assertTrue(comparePdf($generatedContent, $sampleContent));

});

it("imprime el historial de pagos en pantalla", function(){
    /** @var TestCase $this */

    $user = User::find(1);
    $venta = Venta::factory()->credito("3100")->create();
    $credito = Credito::factory()->for($venta, "creditable")->create();
    $credito->build();
    $id = $credito->id;

    $this->mock(HistorialPagos::class, function($mock) use($id){
        $mock->shouldReceive("generate")
            ->once()
            ->with(Mockery::on(function($credito) use($id){
                return $credito->id == $id;
            }))
            ->andReturn(new class {
                function stream($filename = "document.pdf"){
                    return new Response("MOCKED_CONTENT", 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' =>  'inline; filename="' . $filename . '"',
                    ]);
                }
            });
    });

    // $mock = \Mockery::mock(new HistorialPagos);
    // $this->instance(HistorialPagos::class, $mock)->shouldReceive("generate")->once();

    $response = $this->actingAs($user)->get("/creditos/$id/historial_pagos");

    $response->assertOk();
    $response->assertSeeText("MOCKED_CONTENT");
    $this->assertNotEmpty($response->getContent());
    $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    $this->assertEquals('inline; filename="historial_pagos.pdf"', $response->headers->get('Content-Disposition'));
});

