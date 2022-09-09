<?php

use App\Models\Credito;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\UploadedFile;

// it("Verifica que el pago sea mayor o igual que el monto a pagar", function($dataset){

// })->with([
//     function(){
//         $data = Venta::factory([
//             "moneda" => "USD",
//             "importe" => "10530.96",
//         ])->contado()->for(Reserva::factory([
//             "moneda" => "USD",
//             "importe" => "100"
//         ]))->raw() + [
//             "pago" => [
//                 "metodo_pago" => 2,
//                 "moneda" => "USD",
//                 "monto" => "10430.95",
//                 "numero_transaccion" => "1242325848",
//                 "comprobante" => UploadedFile::fake()->image("comprobante.png")
//             ]
//         ];
//     }
// ])

// test("Venta al contado en dolares, reserva en dolares y pago en dolares", function(){
//     /** @var TestCase $this */

//     //Venta al contado
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10430.95",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);

//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10430.96",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);   
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "10430.96");
// });

// test("Venta al contado en dolares, reserva en dolares y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72599.47",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72599.48",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);   
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "10430.96");
// });

// test("Venta al contado en dolares, reserva en bolivianos y pago en dolares", function(){
    
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10430.95",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10430.96",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);   
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "10430.96");
// });

// test("Venta al contado en dolares, reserva en bolivianos y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72599.47",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72599.48",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);   
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "10430.96");
// });

// test("Venta al contado en bolivianos, reserva en dolares y pago en dolares", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10537.94",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10537.95",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "72606.48");
// });

// test("Venta al contado en bolivianos, reserva en dolares y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72606.47",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72606.48",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);   
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "72606.48");
// });

// test("Venta al contado en bolivianos, reserva en bolivianos y pago en dolares", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10536.92",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10536.93",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);   
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "72599.48");
// });

// test("Venta al contado en bolivianos, reserva en bolivianos y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72599.47",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72599.48",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "72599.48");
// });

// //Credito---------------------------------------------------------------------------------------------------------

// test("Venta al credito en dolares, reserva en dolares y pago en dolares", function(){
//     /** @var TestCase $this */

//     //Venta al credito
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "500",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "399.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "400.00",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "400.00");
// });

// test("Venta al credito en dolares, reserva en dolares y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//         "cuota_inicial" => "500",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "500",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2783.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2784",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "400.00");
// });

// test("Venta al credito en dolares, reserva en bolivianos y pago en dolares", function(){
    
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//         "cuota_inicial" => "500",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "500",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "399.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "400",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "400.00");
// });

// test("Venta al credito en dolares, reserva en bolivianos y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//         "cuota_inicial" => "500",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "500",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2783.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2784",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "400.00");
// });

// test("Venta al credito en bolivianos, reserva en dolares y pago en dolares", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "3480",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "405.07",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "405.08",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "2791");
// });


// test("Venta al credito en bolivianos, reserva en dolares y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "3480",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2790.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2791",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "2791");
// });

// test("Venta al credito en bolivianos, reserva en bolivianos y pago en dolares", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "3480",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "404.05",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "404.06",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "2784");
// });

// test("Venta al credito en bolivianos, reserva en bolivianos y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw() + [
//         "credito" => Credito::factory([
//             "cuota_inicial" => "3480",
//         ])->raw(),
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2783.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $data = [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2784",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ] + $data;
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlCredito($data, $venta->credito, "2784");
// });