<?php

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Mockery\MockInterface;

it('Falla si no se proporciona un codigo de pago', function () {
    /** @var TestCase $this  */
    $response = $this->actingAs(User::find(1))->getJson('/api/pagables');

    $response->assertStatus(400);

});

it('verifica la estructura de la respuesta', function(){
    /** @var TestCase $this */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });

    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "importe" => "500"
    ])->for($cliente)->credito("10030.96"), "creditable")->create();
    $credito->build();

    $credito->cuotas[0]->update([
        "saldo" => "155.2700",
        "total_pagos" => "100",
    ]);
    $credito->cuotas[0]->pagos()->create([
        "fecha" => "2022-04-02",
        "moneda" => $credito->getCurrency()->code,
        "importe" => "100"
    ]);

    $response = $this->actingAs(User::find(1))->getJson('/api/pagables?'.http_build_query([
        "fecha" => $credito->cuotas[1]->vencimiento->format("Y-m-d"),
        "codigo_pago"=> $cliente->codigo_pago
    ]));
    $response->assertOk();
    $response->assertJsonStructure([
        "cliente" => [
            "id",
            "nombre_completo"
        ],
        "pagables" => [
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
});

it('obtiene los pagables', function ($dataset) {
    /** @var TestCase $this  */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });    
    $data = $dataset["data"];
    $this->travelTo($dataset["fecha"]);

    $response = $this->actingAs(User::find(1))->getJson('/api/pagables?'.http_build_query($data));

    $response->assertOk();
    expect($response->json("pagables"))->toMatchArray($dataset["expectations"]);
})->with([
    function(){
        $cliente = Cliente::factory()->create();
        $credito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "importe" => "500"
        ])->for($cliente)->credito("10030.96"), "creditable")->create();
        $credito->build();
        return [
            "cliente" => $cliente,
            "credito" => $credito,
            "fecha" => $credito->fecha,
            "data" => [
                "codigo_pago"=> $cliente->codigo_pago
            ],
            "expectations" => [
                [
                    "id" => $credito->cuotas[0]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0000",
                    "total" => "255.2600"
                ]
            ]
        ];
    },
    function(){
        $cliente = Cliente::factory()->create();
        $credito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "importe" => "500"
        ])->for($cliente)->credito("10030.96"), "creditable")->create();
        $credito->build();
        return [
            "cliente" => $cliente,
            "credito" => $credito,
            "fecha" => $credito->cuotas[0]->vencimiento,
            "data" => [
                "codigo_pago"=> $cliente->codigo_pago
            ],
            "expectations" => [
                [
                    "id" => $credito->cuotas[0]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0000",
                    "total" => "255.2600"
                ]
            ]
        ];
    },
    function(){
        $cliente = Cliente::factory()->create();
        $credito = Credito::factory([
            "cuota_inicial" => "500",
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "importe" => "500"
        ])->for($cliente)->credito("10030.96"), "creditable")->create();
        $credito->build();
        return [
            "cliente" => $cliente,
            "credito" => $credito,
            "fecha" => $credito->cuotas[0]->vencimiento->addDay(),
            "data" => [
                "codigo_pago"=> $cliente->codigo_pago
            ],
            "expectations" => [
                [
                    "id" => $credito->cuotas[0]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0200",
                    "total" => "255.2800"
                ],
                [
                    "id" => $credito->cuotas[1]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[1]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0000",
                    "total" => "255.2600"
                ]
            ]
        ];
    },
    function(){
        $cliente = Cliente::factory()->create();
        $credito = Credito::factory([
            "cuota_inicial" => "500",
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "importe" => "500"
        ])->for($cliente)->credito("10030.96"), "creditable")->create();
        $credito->build();

        $credito->cuotas[0]->update([
            "saldo" => "155.2700",
            "total_pagos" => "100",
        ]);
        $credito->cuotas[0]->pagos()->create([
            "fecha" => "2022-04-02",
            "moneda" => $credito->getCurrency()->code,
            "importe" => "100"
        ]);

        return [
            "cliente" => $cliente,
            "credito" => $credito,
            "fecha" => $credito->cuotas[1]->vencimiento,
            "data" => [
                "codigo_pago"=> $cliente->codigo_pago
            ],
            "expectations" => [
                [
                    "id" => $credito->cuotas[0]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "155.2700",
                    "multa" => "0.3900",
                    "total" => "155.6600"
                ],
                [
                    "id" => $credito->cuotas[1]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[1]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0000",
                    "total" => "255.2600"
                ]
            ]
        ];
    },
    function(){
        $cliente = Cliente::factory()->create();
        $credito = Credito::factory([
            "cuota_inicial" => "500",
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "importe" => "500"
        ])->for($cliente)->credito("10030.96"), "creditable")->create();
        $credito->build();

        $credito->cuotas[0]->update([
            "saldo" => "155.2700",
            "total_pagos" => "100",
        ]);
        $credito->cuotas[0]->pagos()->create([
            "fecha" => "2022-04-02",
            "moneda" => $credito->getCurrency()->code,
            "importe" => "100"
        ]);

        return [
            "cliente" => $cliente,
            "credito" => $credito,
            "fecha" => $credito->cuotas[1]->vencimiento->addDays(5),
            "data" => [
                "fecha" => $credito->cuotas[1]->vencimiento->format("Y-m-d"),
                "codigo_pago"=> $cliente->codigo_pago
            ],
            "expectations" => [
                [
                    "id" => $credito->cuotas[0]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "155.2700",
                    "multa" => "0.3900",
                    "total" => "155.6600"
                ],
                [
                    "id" => $credito->cuotas[1]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[1]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0000",
                    "total" => "255.2600"
                ]
            ]
        ];
    },
    function(){
        $cliente = Cliente::factory()->create();
        $credito = Credito::factory([
            "cuota_inicial" => "500",
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1,
            "estado" => 1
        ])->for(Venta::factory([
            "fecha" => "2022-02-28",
            "importe" => "400",
            "saldo" => "100",
            "estado" => 1
        ])->for($cliente)->for(Reserva::factory([
            "importe" => "100",
            "saldo" => "50",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
            "estado" => 1
        ])->for($cliente))->credito("10030.96"), "creditable")->create();
        $credito->build();

        $credito->cuotas[0]->update([
            "saldo" => "155.2700",
            "total_pagos" => "100",
        ]);
        $credito->cuotas[0]->pagos()->create([
            "fecha" => "2022-04-02",
            "moneda" => $credito->getCurrency()->code,
            "importe" => "100"
        ]);

        return [
            "cliente" => $cliente,
            "credito" => $credito,
            "fecha" => $credito->cuotas[1]->vencimiento->addDays(5),
            "data" => [
                "fecha" => $credito->cuotas[1]->vencimiento->format("Y-m-d"),
                "codigo_pago"=> $cliente->codigo_pago
            ],
            "expectations" => [
                [
                    "id" => $credito->creditable->reserva->id,
                    "type" => Reserva::class,
                    "referencia" => $credito->creditable->reserva->getReferencia(),
                    "moneda" => $credito->creditable->reserva->getCurrency()->code,
                    "importe" => "100.0000",
                    "saldo" => "50.0000",
                    "multa" => "0.0000",
                    "total" => "50.0000"
                ],
                [
                    "id" => $credito->creditable->id,
                    "type" => Venta::class,
                    "referencia" => $credito->creditable->getReferencia(),
                    "moneda" => $credito->creditable->getCurrency()->code,
                    "importe" => "400.0000",
                    "saldo" => "100.0000",
                    "multa" => "0.0000",
                    "total" => "100.0000"
                ],
                [
                    "id" => $credito->cuotas[0]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "155.2700",
                    "multa" => "0.3900",
                    "total" => "155.6600"
                ],
                [
                    "id" => $credito->cuotas[1]->id,
                    "type" => Cuota::class,
                    "referencia" => $credito->cuotas[1]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0000",
                    "total" => "255.2600"
                ]
            ]
        ];
    },
]);
