<?php

use App\Events\PagoCuotaCreated;
use App\Jobs\RegistrarTransaccion;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Deposito;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Tests\TestCase;

function buildCredito(){
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
    ])->for($cliente)->credito(), "creditable")->create();
    $credito->build();
    return $credito;
}

// function buildCredito2(){
//     $credito = Credito::factory([
//         "plazo" => 36,
//         "periodo_pago" => 1,
//         "dia_pago" => 1
//     ])->for(Venta::factory([
//         "fecha" => "2022-02-13",
//         "moneda" => "USD",
//         "importe" => "25056.00"
//     ])->for(Cliente::factory())->credito("21056.00"), "creditable")->create();
//     $credito->build();
//     return $credito;
// }


// test("Campos requeridos", function(){
//     /** @var TestCase $this  */
//     $credito = buildCredito();

//     $cuota = $credito->cuotas[0];
//     $response = $this->actingAs(User::find(1))->postJson("/api/pagos/cuotas/$cuota->id", []);
//     $response->assertJsonValidationErrors([
//         "importe" => "El campo 'importe' es requerido",
//     ]);
// });

// test('La fecha no puede estar en el futuro', function(){
//     /** @var TestCase $this */
//     $credito = buildCredito();

//     $cuota = $credito->cuotas[0];

//     $today = Carbon::today();
//     $response = $this->actingAs(User::find(1))->postJson("/api/pagos/cuotas/$cuota->id", [
//         "fecha" => $today->clone()->addDay()->format("Y-m-d"),
//     ]);
//     $response->assertJsonValidationErrors([
//         "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
//     ]);

//     $response = $this->actingAs(User::find(1))->postJson("/api/pagos/cuotas/$cuota->id", [
//         "fecha" => $today->format("Y-m-d")
//     ]);
//     $response->assertJsonMissingValidationErrors([
//         "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
//     ]);
// });

// test('No es una cuota válida.', function () {
//     /** @var TestCase $this  */
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/pagos/cuotas/1000", []);
//     $response->assertNotFound();
// });

// // it('No permite pagos que excedan el monto del depósito', function ($dataset) {
// //     /** @var TestCase $this */
// //     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
// //         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
// //     });
// //     // $credito = $dataset["credito"];
// //     $cuota = $dataset["cuota"];

// //     $this->travelTo($cuota->vencimiento);
// //     $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, $dataset["data"]);
// //     $response->assertStatus(409);
// //     $response->assertJson(["message"=>"El pago excede el saldo del deposito."]);
// // })->with([
// //     function(){
// //         $credito = buildCredito();
// //         $cuota = $credito->cuotas[1];
// //         $data = [
// //             "importe" => "100",
// //             "metodo_pago" => 2,
// //             "deposito" => [
// //                 "numero_transaccion" => $this->faker->randomNumber(),
// //                 "moneda" => "USD",
// //                 "importe" => "99.99",
// //                 "comprobante" => UploadedFile::fake()->image("comprobante.png")
// //             ]
// //         ];
// //         return [
// //             "credito" => $credito,
// //             "cuota" => $cuota,
// //             "data" => $data
// //         ];
// //     },
// //     function(){
// //         $credito = buildCredito();
// //         $cuota = $credito->cuotas[1];
// //         $deposito = Deposito::factory([
// //             "numero_transaccion" => $this->faker->randomNumber(),
// //             "moneda" => "BOB",
// //             "importe" => "1000",
// //             "saldo" => "696.06",
// //             "cliente_id" => $credito->creditable->cliente_id
// //         ])->create();
// //         $data = [
// //             "importe" => "100.01",
// //             "metodo_pago" => 2,
// //             "deposito" => [
// //                 "numero_transaccion" => $deposito->numero_transaccion
// //             ]
// //         ];
// //         return [
// //             "credito" => $credito,
// //             "cuota" => $cuota,
// //             "data" => $data
// //         ];
// //     }
// // ]);

// test('El pago excede el saldo de la cuota', function($dataset) {
//     /** @var TestCase $this */
//     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
//         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
//     });
//     $cuota = $dataset["cuota"];

//     if(isset($dataset["fecha"])) $this->travelTo($dataset["fecha"]);
//     $cuota->projectTo(now());

//     $data = $dataset["data"];

//     $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, $data);
//     $response->assertJsonValidationErrors([
//         "importe" => "El pago excede el saldo de la cuota."
//     ]);

//     $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, ["importe" => (string) $cuota->total->amount]+$data);
//     $response->assertJsonMissingValidationErrors(["importe"]);
// })->with([
//     function(){
//         $credito = buildCredito();
//         $cuota = $credito->cuotas[0];
//         $data = Transaccion::factory()->raw([
//             "importe" => "255.27",
//         ]) + [
//             "deposito" => [
//                 "numero_transaccion" => 1923
//             ]
//         ];
//         unset($data["fecha"]);
//         return [
//             "fecha" => $cuota->vencimiento,
//             "cuota" => $cuota,
//             "data" => $data
//         ];
//     },
//     function(){
//         $credito = buildCredito();
//         $cuota = $credito->cuotas[0];
//         $data = Transaccion::factory()->raw([
//             "importe" => "255.48",
//         ]) + [
//             "deposito" => [
//                 "numero_transaccion" => 1923
//             ]
//         ];
//         unset($data["fecha"]);
//         return [
//             "fecha" => $cuota->vencimiento->addDays(10),
//             "cuota" => $cuota,
//             "data" => $data
//         ];
//     }
// ]);

// test('Solo puede pagar cuotas pendientes o vencidas', function($dataset) {
//     /** @var TestCase $this */
//     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
//         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
//     });
//     $cuota = $dataset["cuota"];
//     $data = $dataset["data"];

//     $this->travelTo($cuota->anterior->vencimiento);

//     $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, $data);
//     $response->assertForbidden();

//     $this->travel(1)->day();

//     $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, $data);
//     expect($response->getStatusCode())->not->toBe(403);
// })->with([
//     function(){
//         $credito = buildCredito();
//         $cuota = $credito->cuotas[1];
//         return [
//             "cuota" => $cuota,
//             "data" => [
//                 "importe" => "100.00",
//             ]
//         ];
//     }
// ]);

// test('registra un pago', function ($dataset) {
//     /** @var TestCase $this  */
//     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
//         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
//     });

//     $cuota = $dataset["cuota"];
    
//     $this->travelTo($dataset["fecha"]);

//     $data = $dataset["data"];
//     $expectations = $dataset["expectations"];
    
//     $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, $data);
//     $response->assertOk();
//     $cuota->refresh();
//     $this->assertSame($expectations, [
//         "saldo" => (string)$cuota->saldo->amount,
//         "total_multas" => (string)$cuota->total_multas->amount,
//         "total_pagos" => (string)$cuota->total_pagos->amount,
//     ]);
// })->with([
//     function(){
//         $credito = buildCredito();
//         $cuota = $credito->cuotas[0];
//         return [
//             "cuota" => $cuota,
//             "fecha" => $cuota->vencimiento,
//             "data" => [
//                 "importe" => "100",
//             ],
//             "expectations" => [
//                 "saldo" => "155.2600",
//                 "total_multas" => "0.0000",
//                 "total_pagos" => "100.0000"
//             ]
//         ];
//     },
//     function(){
//         $credito = buildCredito();
//         $cuota = $credito->cuotas[0];
//         $cuota->pagos()->create([
//             "fecha" => $cuota->vencimiento->format("Y-m-d"),
//             "moneda" => "USD",
//             "importe" => "100"
//         ]);
//         $cuota->total_pagos = "100";
//         $cuota->update();
//         return [
//             "cuota" => $cuota,
//             "fecha" => $cuota->siguiente->vencimiento,
//             "data" => [
//                 "importe" => "155.26",
//             ],
//             "expectations" => [
//                 "saldo" => "0.3900",
//                 "total_multas" => "0.3900",
//                 "total_pagos" => "255.2600"
//             ]
//         ];
//     },
//     function(){
//         $credito = buildCredito();
//         $cuota = $credito->cuotas[0];
//         $cuota->pagos()->create([
//             "fecha" => $cuota->vencimiento->format("Y-m-d"),
//             "moneda" => "USD",
//             "importe" => "100"
//         ]);
//         $cuota->total_pagos = "100";
//         $cuota->update();
//         return [
//             "cuota" => $cuota,
//             "fecha" => $cuota->siguiente->vencimiento->addDays(30),
//             "data" => [
//                 "fecha" => $cuota->siguiente->vencimiento->format("Y-m-d"),
//                 "importe" => "155.26",
//             ],
//             "expectations" => [
//                 "saldo" => "0.3900",
//                 "total_multas" => "0.3900",
//                 "total_pagos" => "255.2600"
//             ]
//         ];
//     },
// ]);

// it('registra la transaccion', function ($dataset) {
//     /** @var TestCase $this  */
//     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
//         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
//     });

//     $cuota = $dataset["cuota"];
//     $data = $dataset["data"];
    
//     $this->travelTo($cuota->vencimiento);

//     Event::fake();
    
//     $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, $data);
//     $response->assertOk();
//     Event::assertDispatched(PagoCuotaCreated::class, function(PagoCuotaCreated $event) use($cuota){
//         $this->assertEquals($event->userId, 1);
//         $this->assertEquals($event->pago->id, $cuota->pagos()->latest("id")->first()->id);
//         return true;
//     });
// })->with([
//     function(){
//         $credito = buildCredito();
//         $cuota = $credito->cuotas[0];
//         return [
//             "cuota" => $cuota,
//             "data" => [
//                 "importe" => "155.26",
//             ]
//         ];
//     }
// ]);

// it('registra pagos', function ($dataset) {
//     /** @var TestCase $this  */
//     $requests = $dataset["requests"];
//     $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
//         $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
//     });
//     foreach($requests as ["cuota" => $cuota, "data" => $data, "expectations" => $expectations]){
//         $response = $this->actingAs(User::find(1))->postJson('/api/pagos/cuotas/'.$cuota->id, $data);
//         $response->assertOk();
//         $cuota->refresh();
//         $this->assertEquals($expectations, [
//             "saldo" => (string) $cuota->saldo->amount,
//             "total_multas" => (string) $cuota->total_multas->amount,
//             "total_pagos" => (string) $cuota->total_pagos->amount
//         ]);
//     }
// })->with([
//     function(){
//         $credito = buildCredito2();
//         return [
//             "requests" => [
//                 [
//                     "cuota" => $credito->cuotas[1],
//                     "data" => [
//                             "fecha" => "2022-07-13",
//                             "importe" => "100",
//                     ],
//                     "expectations" => [
//                         "saldo" => "584.6500",
//                         "total_pagos" => "100.0000",
//                         "total_multas" => "0.6000"
//                     ],
//                 ],
//                 [
//                     "cuota" => $credito->cuotas[3],
//                     "data" => [
//                             "fecha" => "2022-07-13",
//                             "importe" => "160",
//                     ],
//                     "expectations" => [
//                         "saldo" => "524.2100",
//                         "total_pagos" => "160.0000",
//                         "total_multas" => "0.1600"
//                     ],                    
//                 ],
//                 [
//                     "cuota" => $credito->cuotas[1],
//                     "data" => [
//                             "fecha" => "2022-08-22",
//                             "importe" => "590.01",
//                     ],
//                     "expectations" => [
//                         "saldo" => "0.1500",
//                         "total_pagos" => "690.0100",
//                         "total_multas" => "6.1100"
//                     ],                    
//                 ],
//                 [
//                     "cuota" => $credito->cuotas[3],
//                     "data" => [
//                             "fecha" => "2022-08-22",
//                             "importe" => "520.48",
//                     ],
//                     "expectations" => [
//                         "saldo" => "5.9800",
//                         "total_pagos" => "680.4800",
//                         "total_multas" => "2.4100"
//                     ],                    
//                 ],
//                 [
//                     "cuota" => $credito->cuotas[1],
//                     "data" => [
//                             "fecha" => "2022-08-22",
//                             "importe" => "0.15",
//                     ],
//                     "expectations" => [
//                         "saldo" => "0.0000",
//                         "total_pagos" => "690.1600",
//                         "total_multas" => "6.1100"
//                     ],                    
//                 ],
//                 [
//                     "cuota" => $credito->cuotas[3],
//                     "data" => [
//                             "fecha" => "2022-08-22",
//                             "importe" => "6",
//                     ],
//                     "expectations" => [
//                         "saldo" => "0.0000",
//                         "total_pagos" => "686.4800",
//                         "total_multas" => "2.4300"
//                     ],                    
//                 ]
//             ]
//         ];
//     }
// ]);
