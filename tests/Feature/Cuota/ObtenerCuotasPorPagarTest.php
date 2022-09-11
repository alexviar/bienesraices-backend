<?php

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\DetalleTransaccion;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

it('Falla si no se proporciona un codigo de pago', function () {
    /** @var TestCase $this  */
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas');

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
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
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

    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
        "fecha" => $credito->cuotas[1]->vencimiento->format("Y-m-d"),
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
});

it('obtiene las cuotas adeudadas', function ($dataset) {
    /** @var TestCase $this  */
    // $cliente = $dataset["cliente"];
    // $credito = $dataset["credito"];
    $data = $dataset["data"];
    
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });

    $this->travelTo($dataset["fecha"]);
    $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query($data));
    $response->assertOk();
    expect($response->json("cuotas"))->toMatchArray($dataset["expectations"]);
})->with([
    function(){
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
            "importe" => "10530.96"
        ])->for($cliente), "creditable")->create();
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
            "importe" => "10530.96"
        ])->for($cliente), "creditable")->create();
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
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "255.2600",
                    "multa" => "0.0200",
                    "total" => "255.2800"
                ],
                [
                    "id" => $credito->cuotas[1]->id,
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
            "importe" => "10530.96"
        ])->for($cliente), "creditable")->create();
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
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "155.2700",
                    "multa" => "0.3900",
                    "total" => "155.6600"
                ],
                [
                    "id" => $credito->cuotas[1]->id,
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
            "importe" => "10530.96"
        ])->for($cliente), "creditable")->create();
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
                    "referencia" => $credito->cuotas[0]->getReferencia(),
                    "moneda" => $credito->getCurrency()->code,
                    "importe" => "255.2600",
                    "saldo" => "155.2700",
                    "multa" => "0.3900",
                    "total" => "155.6600"
                ],
                [
                    "id" => $credito->cuotas[1]->id,
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



// test('Fecha explicita', function () {
//     /** @var TestCase $this  */
//     $cliente = Cliente::factory()->create();
//     $credito = Credito::factory([
//         "cuota_inicial" => "500",
//         "plazo" => 48,
//         "periodo_pago" => 1,
//         "dia_pago" => 1
//     ])->for(Venta::factory([
//         "fecha" => "2022-02-28",
//         "importe" => "10530.96"
//     ])->for($cliente), "creditable")->create();
//     $credito->build();

//     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
//         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
//     });

//     $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
//         "codigo_pago"=> $cliente->codigo_pago,
//         "fecha" => "2022-02-28"
//     ]));
//     $response->assertOk();
//     $response->assertJsonStructure([
//         "cliente" => [
//             "id",
//             "nombre_completo"
//         ],
//         "cuotas" => [
//             "*" => [
//                 "id",
//                 "referencia",
//                 "moneda",
//                 "importe",
//                 "saldo",
//                 "multa",
//                 "total",
//             ]
//         ]
//     ]);
//     expect($response->json("cuotas"))->toMatchNestedArray([
//         [
//             "id" => $credito->cuotas[0]->id,
//             "referencia" => $credito->cuotas[0]->getReferencia(),
//             "moneda" => $credito->getCurrency()->code,
//             "importe" => "255.2600",
//             "saldo" => "255.2600",
//             "multa" => "0.0000",
//             "total" => "255.2600"
//         ]
//     ]);
    
//     $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
//         "codigo_pago"=> $cliente->codigo_pago,
//         "fecha" => "2022-04-01"
//     ]));
//     $response->assertOk();
//     expect($response->json("cuotas"))->toMatchNestedArray([
//         [
//             "id" => $credito->cuotas[0]->id,
//             "referencia" => $credito->cuotas[0]->getReferencia(),
//             "moneda" => $credito->getCurrency()->code,
//             "importe" => "255.2600",
//             "saldo" => "255.2600",
//             "multa" => "0.0000",
//             "total" => "255.2600"
//         ]
//     ]);
    
//     $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
//         "codigo_pago"=> $cliente->codigo_pago,
//         "fecha" => "2022-04-02"
//     ]));
//     $response->assertOk();
//     expect($response->json("cuotas"))->toMatchNestedArray([
//         [
//             "id" => $credito->cuotas[0]->id,
//             "referencia" => $credito->cuotas[0]->getReferencia(),
//             "moneda" => $credito->getCurrency()->code,
//             "importe" => "255.2600",
//             "saldo" => "255.2600",
//             "multa" => "0.0200",
//             "total" => "255.2800"
//         ],
//         [
//             "id" => $credito->cuotas[1]->id,
//             "referencia" => $credito->cuotas[1]->getReferencia(),
//             "moneda" => $credito->getCurrency()->code,
//             "importe" => "255.2600",
//             "saldo" => "255.2600",
//             "multa" => "0.0000",
//             "total" => "255.2600"
//         ]
//     ]);

//     $transaccion = Transaccion::factory([
//         "fecha" => "2022-04-02"
//     ])->has(DetalleTransaccion::factory([
//         "importe" => "100"
//     ]), "detalles")->create();
//     $transaccion->detalles[0]->cuotas()->attach($credito->cuotas[0]);

//     $credito->cuotas[0]->update([
//         "saldo" => "155.27",
//         "total_pagos" => "100",
//     ]);
    
//     $response = $this->actingAs(User::find(1))->getJson('/api/pagos/cuotas?'.http_build_query([
//         "codigo_pago"=> $cliente->codigo_pago,
//         "fecha" => "2022-05-01"
//     ]));
//     $response->assertOk();
//     expect($response->json("cuotas.0"))->toMatchArray([
//         "id" => $credito->cuotas[0]->id,
//         "referencia" => $credito->cuotas[0]->getReferencia(),
//         "moneda" => $credito->getCurrency()->code,
//         "importe" => "255.2600",
//         "saldo" => "155.2700",
//         "multa" => "0.3900",
//         "total" => "155.6600"
//     ]);
//     expect($response->json("cuotas.1"))->toMatchArray([
//         "id" => $credito->cuotas[1]->id,
//         "referencia" => $credito->cuotas[1]->getReferencia(),
//         "moneda" => $credito->getCurrency()->code,
//         "importe" => "255.2600",
//         "saldo" => "255.2600",
//         "multa" => "0.0000",
//         "total" => "255.2600"
//     ]);
// });
