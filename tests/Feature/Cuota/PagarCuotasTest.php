<?php

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

test("Campos requeridos", function(){
    $response = $this->actingAs(User::find(1))->postJson("/api/pagos/cuotas", []);
    $response->assertJsonValidationErrors([
        "moneda" => "El campo 'moneda' es requerido",
        "importe" => "El campo 'importe' es requerido",
        "numero_transaccion" => "El campo 'n.º de transacción' es requerido.",
        "comprobante" => "El campo 'comprobante' es requerido",
        "detalles" => "El campo 'detalles' es requerido",
    ]);
});

test('La fecha no puede estar en el futuro', function(){
    /** @var TestCase $this */

    $today = Carbon::today();
    $response = $this->actingAs(User::find(1))->postJson("/api/pagos/cuotas", [
        "fecha" => $today->clone()->addDay()->format("Y-m-d")
    ]);
    $response->assertJsonValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);

    $response = $this->actingAs(User::find(1))->postJson("/api/pagos/cuotas", [
        "fecha" => $today->format("Y-m-d")
    ]);
    $response->assertJsonMissingValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);
});

test('No es una cuota válida.', function () {
    /** @var TestCase $this  */
    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $this->travelTo($credito->cuotas[0]->vencimiento);

    $data = Transaccion::factory([
        "moneda" => "USD",
        "importe" => "100"
    ])->raw() + [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[47]->id+1,
                "importe" => "100",
            ]
        ]
    ];
    unset($data["fecha"]);
    
    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', $data);
    $response->assertJsonValidationErrors([
        "detalles.0.cuota_id" => "No existe una cuota con el id proporcionado."
    ]);
});

it('No permite pagos que excedan el monto del depósito', function () {
    /** @var TestCase $this */

    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', [
        "importe" => "100",
        "detalles" => [
            [ "importe" => "50" ],
            [ "importe" => "50.01" ]
        ],
    ]);

    $response->assertJsonValidationErrors([
        "detalles" => "Los pagos exceden el monto depositado."
    ]);
});

test('El pago excede el saldo de la cuota', function() {
    /** @var TestCase $this */
    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $this->travelTo($credito->cuotas[0]->vencimiento);

    $data = Transaccion::factory()->raw([
        "importe" => "256",
    ]);
    unset($data["fecha"]);

    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', [
        "detalles" => [
            [
                "importe" => "255.30",
                "cuota_id" => $credito->cuotas[0]->id,
            ]
        ]
    ]+$data);

    $response->assertJsonValidationErrors([
        "detalles.0.importe" => "El pago excede el saldo de la cuota."
    ]);

    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', [
        "detalles" => [
            [
                "importe" => "255.19",
                "cuota_id" => $credito->cuotas[0]->id,
            ]
        ],
    ]+$data);
    $response->assertJsonMissingValidationErrors(["detalles.0.importe"]);
});

test('Solo puede pagar cuotas pendientes o vencidas', function() {
    /** @var TestCase $this */
    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $this->travelTo($credito->cuotas[0]->vencimiento);

    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[0]->id,
            ],
            [
                "cuota_id" => $credito->cuotas[1]->id,
            ],
            [
                "cuota_id" => $credito->cuotas[2]->id,
            ]
        ]
    ]);
    $response->assertJsonValidationErrors([
        "detalles.1.cuota_id" => "Solo puede registrar pagos de cuotas pendientes.",
        "detalles.2.cuota_id" => "Solo puede registrar pagos de cuotas pendientes.",
    ]);

    $this->travel(1)->day();

    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[0]->id,
            ],
            [
                "cuota_id" => $credito->cuotas[1]->id,
            ],
            [
                "cuota_id" => $credito->cuotas[2]->id,
            ]
        ]
    ]);
    $response->assertJsonMissingValidationErrors([
        "detalles.1.cuota_id"
    ]);
    $response->assertJsonValidationErrors([
        "detalles.2.cuota_id" => "Solo puede registrar pagos de cuotas pendientes.",
    ]);
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
        "moneda" => "USD",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $this->travelTo($credito->cuotas[0]->vencimiento);

    $data = Transaccion::factory([
        "moneda" => "USD",
        "importe" => "100",
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ])->raw() + [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[0]->id,
                "importe" => "100",
            ]
        ]
    ];
    unset($data["fecha"]);
    
    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', $data);
    $response->assertCreated();
    $credito->cuotas[0]->refresh();
    $this->assertSame((string)$credito->cuotas[0]->saldo->amount, "155.2600");
    $this->assertSame((string)$credito->cuotas[0]->total_multas->amount, "0.0000");
    $this->assertSame((string)$credito->cuotas[0]->total_pagos->amount, "100.0000");

    $this->travelTo($credito->cuotas[1]->vencimiento);

    $data = Transaccion::factory([
        "moneda" => "USD",
        "importe" => "255.26",
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ])->raw() + [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[0]->id,
                "importe" => "155.26",
            ],
            [
                "cuota_id" => $credito->cuotas[1]->id,
                "importe" => "100",
            ]
        ]
    ];
    unset($data["fecha"]);
    
    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', $data);
    $response->assertCreated();
    $credito->cuotas[0]->refresh();
    $this->assertSame((string)$credito->cuotas[0]->saldo->amount, "0.3900");
    $this->assertSame((string)$credito->cuotas[0]->total_multas->amount, "0.3900");
    $this->assertSame((string)$credito->cuotas[0]->total_pagos->amount, "255.2600");
    $credito->cuotas[1]->refresh();
    $this->assertSame((string)$credito->cuotas[1]->saldo->amount, "155.2600");
    $this->assertSame((string)$credito->cuotas[1]->total_multas->amount, "0.0000");
    $this->assertSame((string)$credito->cuotas[1]->total_pagos->amount, "100.0000");

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
        "moneda" => "USD",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $data = Transaccion::factory([
        "fecha" => $credito->cuotas[0]->vencimiento->format("Y-m-d"),
        "moneda" => "USD",
        "importe" => "100",
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ])->raw() + [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[0]->id,
                "importe" => "100",
            ]
        ]
    ];
    
    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', $data);
    $response->assertCreated();
    $credito->cuotas[0]->refresh();
    $this->assertSame((string)$credito->cuotas[0]->saldo->amount, "155.2600");
    $this->assertSame((string)$credito->cuotas[0]->total_multas->amount, "0.0000");
    $this->assertSame((string)$credito->cuotas[0]->total_pagos->amount, "100.0000");

    $data = Transaccion::factory([
        "fecha" => $credito->cuotas[1]->vencimiento->format("Y-m-d"),
        "moneda" => "USD",
        "importe" => "255.26",
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ])->raw() + [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[0]->id,
                "importe" => "155.26",
            ],
            [
                "cuota_id" => $credito->cuotas[1]->id,
                "importe" => "100",
            ]
        ]
    ];
    
    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', $data);
    $response->assertCreated();
    $credito->cuotas[0]->refresh();
    $this->assertSame((string)$credito->cuotas[0]->saldo->amount, "0.3900");
    $this->assertSame((string)$credito->cuotas[0]->total_multas->amount, "0.3900");
    $this->assertSame((string)$credito->cuotas[0]->total_pagos->amount, "255.2600");
    $credito->cuotas[1]->refresh();
    $this->assertSame((string)$credito->cuotas[1]->saldo->amount, "155.2600");
    $this->assertSame((string)$credito->cuotas[1]->total_multas->amount, "0.0000");
    $this->assertSame((string)$credito->cuotas[1]->total_pagos->amount, "100.0000");

});

it('registra la transaccion', function () {
    /** @var TestCase $this  */
    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "10530.96"
    ])->for($cliente), "creditable")->create();
    $credito->build();

    $this->travelTo($credito->cuotas[0]->vencimiento);

    $data = Transaccion::factory([
        "moneda" => "USD",
        "importe" => "100.0000",
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ])->raw() + [
        "detalles" => [
            [
                "cuota_id" => $credito->cuotas[0]->id,
                "importe" => "100",
            ]
        ]
    ];
    unset($data["fecha"]);
    
    $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', $data);
    $response->assertCreated();
    $credito->cuotas[0]->refresh();
    $this->assertSame((string)$credito->cuotas[0]->saldo->amount, "155.2600");
    $this->assertSame((string)$credito->cuotas[0]->total_multas->amount, "0.0000");
    $this->assertSame((string)$credito->cuotas[0]->total_pagos->amount, "100.0000");

    $transaccion = Transaccion::find($response->json("id"));
    expect($transaccion->getAttributes())->toMatchArray([
        "fecha" => $credito->cuotas[0]->vencimiento->format("Y-m-d"),
        "moneda" => $data["moneda"],
        "importe" => $data["importe"],
        "comprobante" => "",
    ]);

    expect($transaccion->detalles[0]->getAttributes())->toMatchArray([
        "moneda" => "USD",
        "importe" => "100.0000",
    ]);
    expect($transaccion->detalles[0]->cuotas[0]->id)->toBe($credito->cuotas[0]->id);    
});


dataset("pagos_dataset", function(){
    function prepareCredito1(){
        $credito = Credito::factory([
            "cuota_inicial" => "4000",
            "plazo" => 36,
            "periodo_pago" => 1,
            "dia_pago" => 1
        ])->for(Venta::factory([
            "fecha" => "2022-02-13",
            "moneda" => "USD",
            "importe" => "25056.00"
        ])->for(Cliente::factory()), "creditable")->create();
        $credito->build();
        return $credito;
    }
    return [
        function(){
            $credito = prepareCredito1();
            return [
                "credito" => $credito,
                "requests" => [
                    [
                        "data" => Transaccion::factory([
                                "fecha" => "2022-07-13",
                                "moneda" => "USD",
                                "importe" => "260",
                                "comprobante" => UploadedFile::fake()->image("comprobante.png")
                            ])->raw() + [
                                "detalles" => [
                                    [
                                        "cuota_id" => $credito->cuotas[1]->id,
                                        "importe" => "100",
                                    ],
                                    [
                                        "cuota_id" => $credito->cuotas[3]->id,
                                        "importe" => "160",
                                    ]
                                ]
                            ],
                        "expectations" => [
                            [
                                "numero" => 2,
                                "saldo" => "584.6500",
                                "total_pagos" => "100.0000",
                                "total_multas" => "0.6000"
                            ],
                            [
                                "numero" => 4,
                                "saldo" => "524.2100",
                                "total_pagos" => "160.0000",
                                "total_multas" => "0.1600"
                            ],
                        ]
                    ],
                    [
                        "data" => Transaccion::factory([
                            "fecha" => "2022-08-22",
                            "moneda" => "USD",
                            "importe" => "1110.5",
                            "comprobante" => UploadedFile::fake()->image("comprobante.png")
                        ])->raw() + [
                            "detalles" => [
                                [
                                    "cuota_id" => $credito->cuotas[1]->id,
                                    "importe" => "590.01",
                                ],
                                [
                                    "cuota_id" => $credito->cuotas[3]->id,
                                    "importe" => "520.48",
                                ]
                            ]
                        ],
                        "expectations" => [
                            [
                                "numero" => 2,
                                "saldo" => "0.1500",
                                "total_pagos" => "690.0100",
                                "total_multas" => "6.1100"
                            ],
                            [
                                "numero" => 4,
                                "saldo" => "5.9800",
                                "total_pagos" => "680.4800",
                                "total_multas" => "2.4100"
                            ],
                        ]
                    ],
                    [
                        "data" => Transaccion::factory([
                            "fecha" => "2022-08-22",
                            "moneda" => "USD",
                            "importe" => "6.15",
                            "comprobante" => UploadedFile::fake()->image("comprobante.png")
                        ])->raw() + [
                            "detalles" => [
                                [
                                    "cuota_id" => $credito->cuotas[1]->id,
                                    "importe" => "0.15",
                                ],
                                [
                                    "cuota_id" => $credito->cuotas[3]->id,
                                    "importe" => "6",
                                ]
                            ]
                        ],
                        "expectations" => [
                            [
                                "numero" => 2,
                                "saldo" => "0.0000",
                                "total_pagos" => "690.1600",
                                "total_multas" => "6.1100"
                            ],
                            [
                                "numero" => 4,
                                "saldo" => "0.0000",
                                "total_pagos" => "686.4800",
                                "total_multas" => "2.4300"
                            ],
                        ]
                    ]
                ]
            ];
        }
    ];
});


it('registra pagos', function ($dataset) {
    /** @var TestCase $this  */
    $credito = $dataset["credito"];
    $requests = $dataset["requests"];
    foreach($requests as ["data" => $data, "expectations" => $expectations]){
        $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas', $data);
        $response->assertCreated();
        foreach($expectations as $expectation){
            $cuota = $credito->cuotas->where("numero", $expectation["numero"])->first()->refresh();
            $this->assertSame($expectation["saldo"], (string) $cuota->saldo->amount);
            $this->assertSame($expectation["total_multas"], (string) $cuota->total_multas->amount);
            $this->assertSame($expectation["total_pagos"], (string) $cuota->total_pagos->amount);
        }
    }
})->with("pagos_dataset");
