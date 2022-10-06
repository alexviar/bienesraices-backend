<?php

use App\Models\Account;
use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Credito;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

function buildCredito2(){
    $credito = Credito::factory([
        "plazo" => 36,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-13",
        "moneda" => "USD",
        "importe" => "25056.00"
    ])->for(Cliente::factory())->credito("21056.00"), "creditable")->create();
    $credito->build();
    return $credito;
}

test("Campos requeridos", function($dataset){
    /** @var TestCase $this */
    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', $dataset["data"]);
    $response->assertJsonValidationErrors($dataset["errors"]);
    $response->assertJsonMissingValidationErrors($dataset["missings"]);
})->with([
    fn () => [
        "data" => [],
        "errors" => [
            "cliente_id" => "El campo 'cliente' es requerido",
            "moneda" => "El campo 'moneda de pago' es requerido",
            "detalles" => "El campo 'detalles' es requerido",
            "medios_pago" => "El campo 'medios de pago' es requerido",
        ],
        "missings" => []
    ],
    fn () => [
        "data" => [
            "detalles"=>[],
            "medios_pago" =>[]
        ],
        "errors" => [
            "detalles" => "El campo 'detalles' es requerido",
            "medios_pago" => "El campo 'medios de pago' es requerido",
        ],
        "missings" => []
    ],
    fn()=>[
        "data" => [
            "detalles"=>[
                [

                ]
            ],
            "medios_pago"=>[
                [

                ]
            ],
            "missings" => []
        ],
        "errors" => [
            "detalles.0.id" => "El campo 'id' es requerido",
            "detalles.0.type" => "El campo 'tipo' es requerido",
            "detalles.0.importe" => "El campo 'importe' es requerido",

            "medios_pago.0.forma_pago" => "El campo 'forma de pago' es requerido",
            "medios_pago.0.importe" => "El campo 'importe' es requerido"
        ],
        "missings" => [
            "medios_pago.0.numero_comprobante",
            "medios_pago.0.comprobante"
        ]
    ],
    fn () => [
        "data" => [
            "detalles"=>[
                [

                ]
            ],
            "medios_pago"=>[
                [
                    "forma_pago" => 2,
                ],
                [
                    "forma_pago" => 1,
                ],
                [
                    "forma_pago" => 1,
                ],
                [
                    "forma_pago" => 2,
                ]
            ]
        ],
        "errors" => [
            "medios_pago.0.numero_comprobante" => "El campo 'n.º de comprobante' es requerido cuando el campo 'forma de pago' es 2",
            "medios_pago.0.comprobante" => "El campo 'comprobante' es requerido cuando el campo 'forma de pago' es 2",
            "medios_pago.3.numero_comprobante" => "El campo 'n.º de comprobante' es requerido cuando el campo 'forma de pago' es 2",
            "medios_pago.3.comprobante" => "El campo 'comprobante' es requerido cuando el campo 'forma de pago' es 2"
        ],
        "missings" => [
            "medios_pago.1.numero_comprobante",
            "medios_pago.1.comprobante",
            "medios_pago.2.numero_comprobante",
            "medios_pago.2.comprobante",
        ]
    ]
]);

it('registra pagos', function ($dataset) {
    /** @var TestCase $this  */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });

    $pagables = collect($dataset["pagables"]);
    $data = $dataset["data"];
    $expectations = $dataset["expectations"];

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', $data);
    
    $response->assertCreated();
    $pagables->each->refresh();
    $this->assertEquals($expectations["pagables"], collect($pagables)->map(function($pagable){
        return $pagable instanceof Cuota ? [
            "saldo" => (string) $pagable->saldo->amount,
            // "total" => (string) $pagable->total->amount,
            "total_multas" => (string) $pagable->total_multas->amount,
            "total_pagos" => (string) $pagable->total_pagos->amount
        ] : [
            "saldo" => (string) $pagable->saldo->amount,
        ];
    })->toArray());
    $cuenta = Account::where("cliente_id", $data["cliente_id"])
        ->where("moneda", $data["moneda"])
        ->first();
    $this->assertEquals(
        $expectations["cuenta"],
        $cuenta ? [
            "balance" => $cuenta->getAttributes()["balance"]
        ] : []
    );
})->with([
    function(){
        $credito = buildCredito2();
        $pagables = [$credito->cuotas[1], $credito->cuotas[3]];
        return [
            "pagables" => $pagables,
            "data" => [
                "fecha" => "2022-07-13",
                "cliente_id" => $credito->creditable->cliente_id,
                "registrar_excedentes" => 1,
                "moneda" => "USD",
                "detalles" => [
                    [
                        "id" => $pagables[0]->codigo,
                        "type" => Cuota::class,
                        "importe" => "100"
                    ],
                    [
                        "id" => $pagables[1]->codigo,
                        "type" => Cuota::class,
                        "importe" => "160"
                    ]
                ],
                "medios_pago" => [
                    [
                        "forma_pago" => 1,
                        "importe" => "260"
                    ]
                ]
            ],
            "expectations" => [
                "pagables" => [
                    [
                        "saldo" => "584.6500",
                        "total_pagos" => "100.0000",
                        "total_multas" => "0.6000"
                    ],
                    [
                        "saldo" => "524.2100",
                        "total_pagos" => "160.0000",
                        "total_multas" => "0.1600"
                    ]
                ],
                "cuenta" => []
            ],
        ];
    },
    function(){
        $credito = buildCredito2();
        $pagables = [$credito->cuotas[1], $credito->cuotas[3]];
        $pagables[0]->update([
            "saldo" => "584.65",
            "total_pagos" => "100.00",
        ]);
        $pagables[0]->pagos()->create([
            "fecha" => "2022-07-13",
            "moneda" => "USD",
            "importe" => "100.00"
        ]);
        $pagables[1]->update([
            "saldo" => "524.21",
            "total_pagos" => "160.00",
        ]);
        $pagables[1]->pagos()->create([
            "fecha" => "2022-07-13",
            "moneda" => "USD",
            "importe" => "160.00"
        ]);
        return [
            "pagables" => $pagables,
            "data" => [
                "fecha" => "2022-08-22",
                "cliente_id" => $credito->creditable->cliente_id,
                "registrar_excedentes" => 1,
                "moneda" => "USD",
                "detalles" => [
                    [
                        "id" => $pagables[0]->codigo,
                        "type" => Cuota::class,
                        "importe" => "590.01"
                    ],
                    [
                        "id" => $pagables[1]->codigo,
                        "type" => Cuota::class,
                        "importe" => "520.48"
                    ]
                ],
                "medios_pago" => [
                    [
                        "forma_pago" => 2,
                        "importe" => "1110.5",
                        "numero_comprobante" => 13243254365,
                        "comprobante" => UploadedFile::fake()->image("comprobante.jpg")
                    ]
                ]
            ],
            "expectations" => [
                "pagables" => [
                    [
                        "saldo" => "0.1500",
                        "total_pagos" => "690.0100",
                        "total_multas" => "6.1100"
                    ],
                    [
                        "saldo" => "5.9800",
                        "total_pagos" => "680.4800",
                        "total_multas" => "2.4100"
                    ]
                ],
                "cuenta" => [
                    "balance" => "0.0100"
                ]
            ],
        ];
    },
    function(){
        $credito = buildCredito2();
        Account::create([
            "moneda" => "USD",
            "balance" => "0.01",
            "cliente_id" => $credito->creditable->cliente_id
        ]);
        $pagables = [$credito->cuotas[1], $credito->cuotas[3]];
        $pagables[0]->update([
            "saldo" => "0.15",
            "total_pagos" => "690.01",
        ]);
        $pagables[0]->pagos()->createMany([[
            "fecha" => "2022-07-13",
            "moneda" => "USD",
            "importe" => "100.00"
        ], [
            "fecha" => "2022-08-22",
            "moneda" => "USD",
            "importe" => "590.01"
        ]]);
        $pagables[1]->update([
            "saldo" => "5.98",
            "total_pagos" => "680.48",
        ]);
        $pagables[1]->pagos()->createMany([[
            "fecha" => "2022-07-13",
            "moneda" => "USD",
            "importe" => "160.00"
        ], [
            "fecha" => "2022-08-22",
            "moneda" => "USD",
            "importe" => "520.48"
        ]]);
        return [
            "pagables" => $pagables,
            "data" => [
                "fecha" => "2022-08-22",
                "cliente_id" => $credito->creditable->cliente_id,
                "registrar_excedentes" => 1,
                "moneda" => "USD",
                "detalles" => [
                    [
                        "id" => $pagables[0]->codigo,
                        "type" => Cuota::class,
                        "importe" => "0.15"
                    ],
                    [
                        "id" => $pagables[1]->codigo,
                        "type" => Cuota::class,
                        "importe" => "6.00"
                    ]
                ],
                "medios_pago" => [
                    [
                        "forma_pago" => 1,
                        "importe" => "7"
                    ]
                ]
            ],
            "expectations" => [
                "pagables" => [
                    [
                        "saldo" => "0.0000",
                        "total_pagos" => "690.1600",
                        "total_multas" => "6.1100"
                    ],
                    [
                        "saldo" => "0.0000",
                        "total_pagos" => "686.4800",
                        "total_multas" => "2.4300"
                    ]
                ],
                "cuenta" => [
                    "balance" => "0.8600"
                ]
            ],
        ];
    }


    // function(){
    //     $credito = buildCredito2();
    //     return [
    //         "requests" => [
    //             [
    //                 "cuota" => $credito->cuotas[1],
    //                 "data" => [
    //                         "fecha" => "2022-07-13",
    //                         "importe" => "100",
    //                 ],
    //                 "expectations" => [
    //                     "saldo" => "584.6500",
    //                     "total_pagos" => "100.0000",
    //                     "total_multas" => "0.6000"
    //                 ],
    //             ],
    //             [
    //                 "cuota" => $credito->cuotas[3],
    //                 "data" => [
    //                         "fecha" => "2022-07-13",
    //                         "importe" => "160",
    //                 ],
    //                 "expectations" => [
    //                     "saldo" => "524.2100",
    //                     "total_pagos" => "160.0000",
    //                     "total_multas" => "0.1600"
    //                 ],                    
    //             ],
    //             [
    //                 "cuota" => $credito->cuotas[1],
    //                 "data" => [
    //                         "fecha" => "2022-08-22",
    //                         "importe" => "590.01",
    //                 ],
    //                 "expectations" => [
    //                     "saldo" => "0.1500",
    //                     "total_pagos" => "690.0100",
    //                     "total_multas" => "6.1100"
    //                 ],                    
    //             ],
    //             [
    //                 "cuota" => $credito->cuotas[3],
    //                 "data" => [
    //                         "fecha" => "2022-08-22",
    //                         "importe" => "520.48",
    //                 ],
    //                 "expectations" => [
    //                     "saldo" => "5.9800",
    //                     "total_pagos" => "680.4800",
    //                     "total_multas" => "2.4100"
    //                 ],                    
    //             ],
    //             [
    //                 "cuota" => $credito->cuotas[1],
    //                 "data" => [
    //                         "fecha" => "2022-08-22",
    //                         "importe" => "0.15",
    //                 ],
    //                 "expectations" => [
    //                     "saldo" => "0.0000",
    //                     "total_pagos" => "690.1600",
    //                     "total_multas" => "6.1100"
    //                 ],                    
    //             ],
    //             [
    //                 "cuota" => $credito->cuotas[3],
    //                 "data" => [
    //                         "fecha" => "2022-08-22",
    //                         "importe" => "6",
    //                 ],
    //                 "expectations" => [
    //                     "saldo" => "0.0000",
    //                     "total_pagos" => "686.4800",
    //                     "total_multas" => "2.4300"
    //                 ],                    
    //             ]
    //         ]
    //     ];
    // }
]);


test('La fecha no puede estar en el futuro', function(){
    /** @var TestCase $this */

    $today = Carbon::today();
    $response = $this->actingAs(User::find(1))->postJson("/api/transacciones", [
        "fecha" => $today->clone()->addDay()->format("Y-m-d")
    ]);
    $response->assertJsonValidationErrors([
        "fecha" => "El campo 'fecha' debe ser anterior o igual a la fecha actual."
    ]);

    $response = $this->actingAs(User::find(1))->postJson("/api/transacciones", [
        "fecha" => $today->format("Y-m-d")
    ]);
    $response->assertJsonMissingValidationErrors([
        "fecha"
    ]);
});

it('No permite pagos que excedan el monto del depósito', function () {
    /** @var TestCase $this */
 
    $credito = buildCredito2();
    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "moneda" => "USD",
        "cliente_id" => $credito->creditable->cliente_id,
        "detalles" => [
            [
                "id" => $credito->cuotas[0]->getMorphKey(),
                "type" => $credito->cuotas[0]->getMorphClass(),
                "importe" => "50"
            ],
            [
                "id" => $credito->cuotas[1]->getMorphKey(),
                "type" => $credito->cuotas[1]->getMorphClass(),
                "importe" => "50.01"
            ]
        ],
        "medios_pago" => [
            [
                "forma_pago" => 1,
                "importe" => "100"
            ]
        ]
    ]);
    $response->assertStatus(500);
    $response->assertJson([
        "message" => "La suma de los pagos es inferior al importe a pagar."
    ]);
});

test('El pago excede el saldo de la cuota', function() {
    /** @var TestCase $this */

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-02-28"));

    $venta = Venta::factory([
        "fecha" => now(),
        "importe" => "10530.96"
    ])->credito("10030.96")->create();
    // $venta->crearPlanPago();
    $credito = Credito::factory([
        "dia_pago" => 31,
        "plazo" => 48,
        "periodo_pago" => 1
    ])->for($venta, "creditable")->create();
    $credito->build();

    $this->travelTo($credito->cuotas[0]->vencimiento);

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "cliente_id" => $venta->cliente_id,
        "moneda" => $venta->moneda,
        "detalles" => [
            [
                "importe" => "255.20",
                "id" => $credito->cuotas[0]->getMorphKey(),
                "type" => $credito->cuotas[0]->getMorphClass()
            ]
        ],
        "medios_pago" => [
            [
                "forma_pago" => 2,
                "importe" => "256",
                "numero_comprobante" => 123124354236,
                "comprobante" => UploadedFile::fake()->image("comprobante.png")
            ]
        ]
    ]);
    $response->assertStatus(500);
    $response->assertJson([
        "message" => "El pago excede el importe a pagar."
    ]);

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "cliente_id" => $venta->cliente_id,
        "moneda" => $venta->moneda,
        "detalles" => [
            [
                "importe" => "255.19",
                "id" => $credito->cuotas[0]->getMorphKey(),
                "type" => $credito->cuotas[0]->getMorphClass()
            ]
        ],
        "medios_pago" => [
            [
                "forma_pago" => 2,
                "importe" => "256",
                "numero_comprobante" => 123124354236,
                "comprobante" => UploadedFile::fake()->image("comprobante.png")
            ]
        ]
    ]);
    $response->assertCreated();
});

test('Solo puede pagar cuotas en curso o vencidas', function() {
    /** @var TestCase $this */

    $venta = Venta::factory([
        "fecha" => now(),
        "importe" => "10530.96"
    ])->credito("10030.96")->create();
    // $venta->crearPlanPago();
    $credito = Credito::factory([
        "dia_pago" => 31,
        "plazo" => 48,
        "periodo_pago" => 1
    ])->for($venta, "creditable")->create();
    $credito->build();

    $this->travelTo($credito->cuotas[0]->vencimiento);

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "cliente_id" => $venta->cliente_id,
        "moneda" => $venta->moneda,
        "detalles" => [
            [
                "importe" => "255.19",
                "id" => $credito->cuotas[0]->getMorphKey(),
                "type" => $credito->cuotas[0]->getMorphClass(),
            ],
            [
                "importe" => "255.19",
                "id" => $credito->cuotas[1]->getMorphKey(),
                "type" => $credito->cuotas[0]->getMorphClass(),
            ]
        ],
        "medios_pago" => [
            [
                "forma_pago" => 2,
                "importe" => "511",
                "numero_comprobante" => 12215243653654,
                "comprobante" => UploadedFile::fake()->image("comprobante.png")
            ]
        ]
    ]);
    $response->assertStatus(500);
    $response->assertJson([
        "message" => "Solo puede pagar cuotas vencidas o en curso."
    ]);
});