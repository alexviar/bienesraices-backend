<?php

// use App\Models\Cuota;
// use App\Models\Credito;
// use App\Models\Transaccion;
// use App\Models\User;
// use App\Models\Venta;
// use Illuminate\Http\UploadedFile;
// use Illuminate\Support\Carbon;
// use Tests\TestCase;


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