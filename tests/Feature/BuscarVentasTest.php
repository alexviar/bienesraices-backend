<?php

use App\Models\Credito;
use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Tests\TestCase;

it('verifica la estructura de la respuesta', function () {
    /** @var TestCase $this */
    $proyecto = Proyecto::factory()->create();
    $plano = Plano::factory()->for($proyecto)->create();
    $ventas = Venta::factory(10)->for(Lote::factory()->for(Manzana::factory()->for($plano)))->credito("100")->create();
    $ventas->each(function($venta){
        Credito::factory()->for($venta, "creditable")->create();
    });
    $response = $this->actingAs(User::find(1))->getJson("/api/proyectos/$proyecto->id/ventas");

    $response->assertStatus(200);
    $response->assertJsonCount(10, "records");
    $response->assertJsonStructure([
        "meta" => [
            "total_records"
        ],
        "records" => [
            "*" => [
                "id",
                "fecha",
                "tipo",
                "proyecto_id",
                "lote_id",
                "cliente_id",
                "vendedor_id",
                "importe" => [
                    "amount",
                    "currency"
                ],
                "cliente" => [
                    "nombre_completo",
                    "documento_identidad" => [
                        "numero",
                        "tipo",
                        "tipo_text",
                    ]
                ],
                "lote" => [
                    "numero",
                    "manzana" => [
                        "numero"
                    ]
                ],
                "credito" => [
                    "id",
                    "codigo",
                    "url_plan_pago",
                    "url_historial_pagos"
                ]
            ]
        ]
    ]);
});
