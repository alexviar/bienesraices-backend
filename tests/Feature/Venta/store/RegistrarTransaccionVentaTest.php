<?php

use App\Events\VentaCreated;
use App\Listeners\TransaccionSubscriber;
use App\Models\Credito;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\UploadedFile;

test("registra transaccion por venta", function($dataset){
    $venta = $dataset["venta"];

    $event = new VentaCreated($venta, User::find(1)->id);
    $subscriber = new TransaccionSubscriber();
    $subscriber->handleVentaCreated($event);

    assertTransaccionPorVenta($venta);
})->with([
    function(){
        $venta = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.9600",
        ])->contado()->withoutReserva()->create();
        return [
            "venta" => $venta,
        ];
    },
    function(){
        $venta = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.9600",
        ])->credito()->has(Credito::factory([
            "cuota_inicial" => "500.0000"
        ]))->withoutReserva()->create();
        return [
            "venta" => $venta,
        ];
    }
]);
// test("Venta al contado en dolares, reserva en dolares y pago en dolares", function(){
//     /** @var TestCase $this */
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "10431",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);    
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "10430.96");
// });

// test("Venta al contado en dolares, reserva en dolares y pago en bolivianos", function(){
//     /** @var TestCase $this */
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//     ])->contado()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw() + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "72600",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];
    
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
//             "monto" => "10431",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];
    
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
//             "monto" => "72600",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

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
//             "monto" => "10538",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

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
//             "monto" => "72607",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

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
//             "monto" => "10537",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

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
//             "monto" => "72600",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
//     $response->assertCreated();
//     $venta = Venta::find($response->json("id"));
//     assertTransaccionPorVentaAlContado($data, $venta, "72599.48");
// });

// // //Credito---------------------------------------------------------------------------------------------------------

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
//             "monto" => "401.00",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ];

//     $proyectoId = $data["proyecto_id"];

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
//     ]))->raw();

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2783.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2784",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonMissingValidationErrors(["pago.monto"]);
// });

// test("Venta al credito en dolares, reserva en bolivianos y pago en dolares", function(){
    
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//         "cuota_inicial" => "500",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw();

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "399.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "400",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonMissingValidationErrors(["pago.monto"]);
// });

// test("Venta al credito en dolares, reserva en bolivianos y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "USD",
//         "importe" => "10530.96",
//         "cuota_inicial" => "500",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw();

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2783.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2784",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonMissingValidationErrors(["pago.monto"]);
// });

// test("Venta al credito en bolivianos, reserva en dolares y pago en dolares", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//         "cuota_inicial" => "3480",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw();

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "405.07",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "405.08",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonMissingValidationErrors(["pago.monto"]);
// });


// test("Venta al credito en bolivianos, reserva en dolares y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//         "cuota_inicial" => "3480",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "USD",
//         "importe" => "100"
//     ]))->raw();

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2790.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2791",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonMissingValidationErrors(["pago.monto"]);
// });

// test("Venta al credito en bolivianos, reserva en bolivianos y pago en dolares", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//         "cuota_inicial" => "3480",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw();

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "404.05",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "USD",
//             "monto" => "404.06",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonMissingValidationErrors(["pago.monto"]);
// });

// test("Venta al credito en bolivianos, reserva en bolivianos y pago en bolivianos", function(){
//     $data = Venta::factory([
//         "moneda" => "BOB",
//         "importe" => "73295.48",
//         "cuota_inicial" => "3480",
//     ])->credito()->for(Reserva::factory([
//         "moneda" => "BOB",
//         "importe" => "696"
//     ]))->raw();

//     $proyectoId = $data["proyecto_id"];

//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2783.99",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonValidationErrors(["pago.monto" => "El pago es menor al monto a pagar"]);
    
//     $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
//         "pago" => [
//             "moneda" => "BOB",
//             "monto" => "2784",
//             "numero_transaccion" => "1242325848",
//             "comprobante" => UploadedFile::fake()->image("comprobante.png")
//         ]
//     ]);
//     $response->assertJsonMissingValidationErrors(["pago.monto"]);
// });