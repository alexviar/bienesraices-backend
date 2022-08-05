<?php

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\User;
use App\Models\Venta;
use Tests\TestCase;

it('Falla si no se proporciona un codigo de pago', function () {
    /** @var TestCase $this  */
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas');

    $response->assertStatus(400);

});

test('Fecha implicita', function () {
    /** @var TestCase $this  */
    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $this->travelTo($credito->creditable->fecha);

    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago
    ]));
    $response->assertOk();
    $response->assertJsonStructure([
        "cliente" => [
            "id",
            "nombre_completo"
        ],
        "cuotas" => [
            "*" => [
                "id",
                "referencia",
                "moneda",
                "importe",
                "saldo",
                "multa",
                "total",
            ]
        ]
    ]);
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);

    $this->travelTo($credito->cuotas[0]->vencimiento);
    
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago
    ]));
    $response->assertOk();
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);
    
    $this->travel(1)->day();
    
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago
    ]));
    $response->assertOk();
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.02",
            "total" => "255.28"
        ],
        [
            "id" => $credito->cuotas[1]->id,
            "referencia" => $credito->cuotas[1]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);

    $credito->cuotas[0]->update([
        "saldo" => "155.28",
        "total_pagos" => "100",
        "total_multas" => "0.02"
    ]);

    $this->travelTo($credito->cuotas[1]->vencimiento);
    
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago
    ]));
    $response->assertOk();
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "155.28",
            "multa" => "0.39",
            "total" => "155.67"
        ],
        [
            "id" => $credito->cuotas[1]->id,
            "referencia" => $credito->cuotas[1]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);
});



test('Fecha explicita', function () {
    /** @var TestCase $this  */
    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago,
        "fecha" => "2022-02-28"
    ]));
    $response->assertOk();
    $response->assertJsonStructure([
        "cliente" => [
            "id",
            "nombre_completo"
        ],
        "cuotas" => [
            "*" => [
                "id",
                "referencia",
                "moneda",
                "importe",
                "saldo",
                "multa",
                "total",
            ]
        ]
    ]);
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);
    
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago,
        "fecha" => "2022-04-01"
    ]));
    $response->assertOk();
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);
    
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago,
        "fecha" => "2022-04-02"
    ]));
    $response->assertOk();
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.02",
            "total" => "255.28"
        ],
        [
            "id" => $credito->cuotas[1]->id,
            "referencia" => $credito->cuotas[1]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);

    $credito->cuotas[0]->update([
        "saldo" => "155.28",
        "total_pagos" => "100",
        "total_multas" => "0.02"
    ]);
    
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "codigo_pago"=> $cliente->codigo_pago,
        "fecha" => "2022-05-01"
    ]));
    $response->assertOk();
    expect($response->json("cuotas"))->toMatchNestedArray([
        [
            "id" => $credito->cuotas[0]->id,
            "referencia" => $credito->cuotas[0]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "155.28",
            "multa" => "0.39",
            "total" => "155.67"
        ],
        [
            "id" => $credito->cuotas[1]->id,
            "referencia" => $credito->cuotas[1]->getReferencia(),
            "moneda" => $credito->getCurrency()->code,
            "importe" => "255.26",
            "saldo" => "255.26",
            "multa" => "0.00",
            "total" => "255.26"
        ]
    ]);
});
