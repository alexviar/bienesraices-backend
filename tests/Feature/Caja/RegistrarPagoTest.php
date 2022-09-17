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
                "moneda" => "USD",
                "detalles" => [
                    [
                        "id" => $pagables[0]->id,
                        "type" => Cuota::class,
                        "importe" => "100"
                    ],
                    [
                        "id" => $pagables[1]->id,
                        "type" => Cuota::class,
                        "importe" => "160"
                    ]
                ],
                "medios_pago" => [
                    [
                        "forma_pago" => 2,
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
                "moneda" => "USD",
                "detalles" => [
                    [
                        "id" => $pagables[0]->id,
                        "type" => Cuota::class,
                        "importe" => "590.01"
                    ],
                    [
                        "id" => $pagables[1]->id,
                        "type" => Cuota::class,
                        "importe" => "520.48"
                    ]
                ],
                "medios_pago" => [
                    [
                        "forma_pago" => 2,
                        "importe" => "1110.5"
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
                "moneda" => "USD",
                "detalles" => [
                    [
                        "id" => $pagables[0]->id,
                        "type" => Cuota::class,
                        "importe" => "0.15"
                    ],
                    [
                        "id" => $pagables[1]->id,
                        "type" => Cuota::class,
                        "importe" => "6.00"
                    ]
                ],
                "medios_pago" => [
                    [
                        "forma_pago" => 2,
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


// test('La fecha no puede estar en el futuro', function(){
//     /** @var TestCase $this */

//     $today = Carbon::today();
//     $response = $this->actingAs(User::find(1))->postJson("/api/transacciones", [
//         "fecha" => $today->clone()->addDay()->format("Y-m-d")
//     ]);
//     $response->assertJsonValidationErrors([
//         "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
//     ]);

//     $response = $this->actingAs(User::find(1))->postJson("/api/transacciones", [
//         "fecha" => $today->format("Y-m-d")
//     ]);
//     $response->assertJsonMissingValidationErrors([
//         "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
//     ]);
// });

// it('No permite pagos que excedan el monto del depósito', function () {
//     /** @var TestCase $this */

//     $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "importe" => "100",
//         "detalles" => [
//             [ "importe" => "50" ],
//             [ "importe" => "50.01" ]
//         ],
//     ]);

//     $response->assertJsonValidationErrors([
//         "detalles" => "Los pagos exceden el monto depositado (Pagos: 100.01, Deposito: 100.00)"
//     ]);
// });

// it('Registra un deposito', function() {
//     /** @var TestCase $this */

//     $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-02-28"));

//     $venta = Venta::factory([
//         "fecha" => now(),
//         "importe" => "10530.96"
//     ])->credito()->create();
//     // $venta->crearPlanPago();
//     $credito = Credito::factory([
//         "dia_pago" => 31,
//         "plazo" => 48,
//         "periodo_pago" => 1
//     ])->for($venta, "creditable")->create();
//     $credito->build();


//     $this->travelTo($credito->cuotas[1]->vencimiento);

//     $data = Transaccion::factory()->raw([
//         "fecha" => "2022-03-30",
//         "importe" => "256",
//     ]);

//     $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "255.19",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);

//     $response->assertCreated();

//     $id = $response->json("id");

//     $transaccion = Transaccion::with("detalles")->find($id);
//     $credito->cuotas[0]->refresh();

//     $this->assertSame("2022-03-30", $transaccion->getAttributes()["fecha"]);
//     $this->assertSame("256.00", $transaccion->getAttributes()["importe"]);
//     $this->assertSame(1, $transaccion->detalles->count());
//     $this->assertSame("255.19", $transaccion->detalles[0]->getAttributes()["importe"]);
//     $this->assertSame(1, $transaccion->detalles[0]->cuotas->count());
//     $this->assertTrue($credito->cuotas[0]->is($transaccion->detalles[0]->cuotas[0]));
//     $this->assertSame("0.00", $credito->cuotas[0]->getAttributes()["saldo"]);
//     $this->assertSame("0.00", $credito->cuotas[0]->getAttributes()["total_multas"]);
//     $this->assertSame("255.19", $credito->cuotas[0]->getAttributes()["total_pagos"]);
// });

// it('Registra un deposito con fecha implicita', function() {
//     /** @var TestCase $this */

//     $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-02-28"));

//     $venta = Venta::factory([
//         "fecha" => now(),
//         "importe" => "10530.96"
//     ])->credito()->create();
//     // $venta->crearPlanPago();
//     $credito = Credito::factory([
//         "dia_pago" => 31,
//         "plazo" => 48,
//         "periodo_pago" => 1
//     ])->for($venta, "creditable")->create();
//     $credito->build();

//     $this->travelTo($credito->cuotas[0]->vencimiento);

//     $data = Transaccion::factory()->raw([
//         "importe" => "256",
//     ]);
//     unset($data["fecha"]);

//     $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "255.19",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);

//     $response->assertCreated();

//     $this->assertDatabaseHas("transacciones", [
//         "fecha" => "2022-03-31"
//     ]);
// });


// test('Registra las multas', function() {
//     /** @var TestCase $this */

//     $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-02-28"));

//     $venta = Venta::factory([
//         "fecha" => now(),
//         "importe" => "10530.96"
//     ])->credito()->create();
//     // $venta->crearPlanPago();
//     $credito = Credito::factory([
//         "dia_pago" => 31,
//         "plazo" => 48,
//         "periodo_pago" => 1
//     ])->for($venta, "creditable")->create();
//     $credito->build();

//     $this->travelTo($credito->cuotas[0]->vencimiento);

//     $data = Transaccion::factory()->raw([
//         "importe" => "50",
//     ]);
//     unset($data["fecha"]);

//     $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "50",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);
//     $credito->cuotas[0]->refresh();
//     $this->assertSame("205.19", $credito->cuotas[0]->getAttributes()["saldo"]);
//     $this->assertSame("0.00", $credito->cuotas[0]->getAttributes()["total_multas"]);
//     $this->assertSame("50.00", $credito->cuotas[0]->getAttributes()["total_pagos"]);

//     $this->travel(1)->day();

//     $data = Transaccion::factory()->raw([
//         "importe" => "100",
//     ]);
//     unset($data["fecha"]);

//     $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "100",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);
//     $credito->cuotas[0]->refresh();
//     $this->assertSame("105.20", $credito->cuotas[0]->getAttributes()["saldo"]);
//     $this->assertSame("0.01", $credito->cuotas[0]->getAttributes()["total_multas"]);
//     $this->assertSame("150.00", $credito->cuotas[0]->getAttributes()["total_pagos"]);

//     $this->travel(29)->days();

//     $data = Transaccion::factory()->raw([
//         "importe" => "106",
//     ]);
//     unset($data["fecha"]);

//     $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "105.46",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);
//     $credito->cuotas[0]->refresh();
//     $this->assertSame("0.00", $credito->cuotas[0]->getAttributes()["saldo"]);
//     $this->assertSame("0.27", $credito->cuotas[0]->getAttributes()["total_multas"]);
//     $this->assertSame("255.46", $credito->cuotas[0]->getAttributes()["total_pagos"]);
// });

// test('El pago excede el saldo de la cuota', function() {
//     /** @var TestCase $this */

//     $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-02-28"));

//     $venta = Venta::factory([
//         "fecha" => now(),
//         "importe" => "10530.96"
//     ])->credito()->create();
//     // $venta->crearPlanPago();
//     $credito = Credito::factory([
//         "dia_pago" => 31,
//         "plazo" => 48,
//         "periodo_pago" => 1
//     ])->for($venta, "creditable")->create();
//     $credito->build();

//     $this->travelTo($credito->cuotas[0]->vencimiento);

//     $data = Transaccion::factory()->raw([
//         "importe" => "256",
//     ]);
//     unset($data["fecha"]);

//     $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "255.20",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);

//     $response->assertJsonValidationErrors([
//         "detalles.0.importe" => "El pago excede el saldo de la cuota."
//     ]);

//     $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "255.19",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);
//     $response->assertCreated();
// });


// test('Solo puede pagar cuotas en curso o vencidas', function() {
//     /** @var TestCase $this */

//     $venta = Venta::factory([
//         "fecha" => now(),
//         "importe" => "10530.96"
//     ])->credito()->create();
//     // $venta->crearPlanPago();
//     $credito = Credito::factory([
//         "dia_pago" => 31,
//         "plazo" => 48,
//         "periodo_pago" => 1
//     ])->for($venta, "creditable")->create();
//     $credito->build();

//     $data = Transaccion::factory()->raw([
//         "importe" => "511",
//     ]);
//     unset($data["fecha"]);

//     $this->travelTo($credito->cuotas[0]->vencimiento);

//     $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
//         "detalles" => [
//             [
//                 "importe" => "255.19",
//                 "cuota_id" => $credito->cuotas[0]->id,
//             ],
//             [
//                 "importe" => "255.19",
//                 "cuota_id" => $credito->cuotas[1]->id,
//             ]
//         ],
//         "comprobante" => UploadedFile::fake()->image("comprobante.png")
//     ]+$data);

//     $response->assertJsonValidationErrors([
//         "detalles.1.cuota_id" => "Solo puede pagar cuotas en curso o vencidas"
//     ]);
// });