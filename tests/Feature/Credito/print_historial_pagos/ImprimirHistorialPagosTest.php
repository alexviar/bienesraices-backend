<?php

use Illuminate\Support\Str;
use App\Http\Reports\Venta\HistorialPagos;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\DetalleTransaccion;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $credito = buildCredito2();

    $response = $this->getJson("/creditos/$credito->codigo/historial-pagos");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $credito = $dataset["credito"];

    $response = $this->actingAs($login)->get("/creditos/$credito->codigo/historial-pagos");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $credito = buildCredito2();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "login" => $login, 
            "credito" => $credito
        ];
    },
    "Proyecto no vinculado" => function(){
        $credito = buildCredito2();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir historial de pagos");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "credito" => $credito
        ];
    },
    "Vendedor no vinculado" => function(){
        $credito = buildCredito2();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir historial de pagos");
        $login->assignRole($rol);
        $login->vendedor()->associate(Vendedor::factory()->create());
        return [
            "login" => $login,
            "credito" => $credito
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $credito = $dataset["credito"];

    $response = $this->actingAs($login)->getJson("/creditos/$credito->codigo/historial-pagos");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        $credito = buildCredito2();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir historial de pagos");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "credito" => $credito
        ];
    },
    "Acceso indirecto" => function(){
        $credito = buildCredito2();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Imprimir historial de pagos");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "credito" => $credito
        ];
    },
    "Proyecto vinculado" => function(){
        $credito = buildCredito2();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir historial de pagos");
        $login->assignRole($rol);
        $login->proyectos()->attach($credito->creditable->proyecto);
        return [
            "login" => $login,
            "credito" => $credito
        ];
    },
    "Vendedor vinculado" => function(){
        $credito = buildCredito2();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir historial de pagos");
        $login->assignRole($rol);
        $login->vendedor()->associate($credito->creditable->vendedor);
        return [
            "login" => $login,
            "credito" => $credito
        ];
    },
]);
#endregion

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
        "tipo" => 1,
        "nombre" => "JOAQUIN",
        "apellido_paterno" => "CHUMACERO",
        "apellido_materno" => "YUPANQUI",

    ]))->for(Lote::factory(["numero" => 2])->for(Manzana::factory(["numero"=>"100"])->for(Plano::factory()->for(Proyecto::factory([
        "nombre" => "OPORTUNIDAD IV"
    ])))))->credito("3100")->create();
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
    $detailModel->pagable()->associate($venta);
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
    $detailModel->pagable()->associate($cuota1);
    $transaccion->detalles()->save($detailModel);
    $cuota1->pagos()->create([
        "fecha" => "2022/08/24",
        "importe" => "70",
        "moneda" => "USD"
    ]);

    $transaccion = Transaccion::factory([
        "fecha" => "2022/09/30",
        "importe" => "87.88",
        "moneda" => "USD"
    ])->create();
    $detailModel = new DetalleTransaccion();
    $detailModel->referencia = "Pago de la cuota 1 del crédito 1";
    $detailModel->moneda = "USD";
    $detailModel->importe = "8.95";
    $detailModel->pagable()->associate($cuota1);
    $transaccion->detalles()->save($detailModel);
    $cuota1->pagos()->create([
        "fecha" => "2022/09/30",
        "importe" => "8.95",
        "moneda" => "USD"
    ]);
    $detailModel = new DetalleTransaccion();
    $detailModel->referencia = "Pago de la cuota 2 del crédito 1";
    $detailModel->moneda = "USD";
    $detailModel->importe = "78.93";
    $detailModel->pagable()->associate($cuota2);
    $transaccion->detalles()->save($detailModel);
    $cuota2->pagos()->create([
        "fecha" => "2022/09/30",
        "importe" => "78.93",
        "moneda" => "USD"
    ]);

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-09-30"));    
    $credito->cuotas->each->refresh();
    $pdf = $report->generate($credito->refresh());
    // $pdf->save(__DIR__."/historial_pagos_sample_10.pdf");

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

    $response = $this->actingAs($user)->get("/creditos/{$credito->codigo}/historial-pagos");

    $response->assertOk();
    $response->assertSeeText("MOCKED_CONTENT");
    $this->assertNotEmpty($response->getContent());
    $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    $this->assertEquals('inline; filename="historial_pagos.pdf"', $response->headers->get('Content-Disposition'));
});

