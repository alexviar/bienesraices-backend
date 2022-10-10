<?php

use Illuminate\Support\Str;
use App\Http\Reports\Venta\HistorialPagos;
use App\Http\Reports\Venta\PlanPagosPdfReporter;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

it("Genera un reporte del plan de pagos", function(){
    /** @var TestCase $this */
    
    $maxId = Cliente::max("id") ?? 0;
    DB::statement('ALTER TABLE clientes AUTO_INCREMENT=' . intval($maxId + 1) . ';');
    DB::statement('START TRANSACTION;');
    $report = new PlanPagosPdfReporter();
    $proyecto = Proyecto::factory([
        "nombre" => "OPORTUNIDAD IV"
    ])->create();
    $venta = Venta::factory([
        "fecha" => "2022/07/28",
        "importe" => "3600",
        "moneda" => "USD",
    ])->for($proyecto)->for(Cliente::factory([
        "tipo" => 1,
        "nombre" => "JOAQUIN",
        "apellido_paterno" => "CHUMACERO",
        "apellido_materno" => "YUPANQUI",

    ]))->for(Lote::factory(["numero" => 2])->for(Manzana::factory(["numero"=>"100"])->for(Plano::factory()->for($proyecto))))->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => "48",
        "dia_pago" => 1,
        "periodo_pago" => 1,
        "tasa_interes" => "0.1"
    ])->for($venta, "creditable")->create();
    $credito->build();
    
    $pdf = $report->generate($credito->refresh());
    // $pdf->save(__DIR__."/plan_pagos_sample_1.pdf");

    $generatedContent = $pdf->output();
    $sampleContent = file_get_contents(__DIR__."/plan_pagos_sample_1.pdf");
    $this->assertTrue(comparePdf($generatedContent, $sampleContent));
});

it("imprime el plan de pagos en pantalla", function(){
    /** @var TestCase $this */

    $user = User::find(1);
    $venta = Venta::factory()->credito()->create();
    $credito = Credito::factory()->for($venta, "creditable")->create();
    $credito->build();
    $id = $credito->id;

    $this->mock(PlanPagosPdfReporter::class, function($mock) use($id){
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

    $response = $this->actingAs($user)->get("/creditos/$id/plan_pagos");

    $response->assertOk();
    $response->assertSeeText("MOCKED_CONTENT");
    $this->assertNotEmpty($response->getContent());
    $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    $this->assertEquals('inline; filename="plan_pagos.pdf"', $response->headers->get('Content-Disposition'));
});

