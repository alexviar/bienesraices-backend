<?php

use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

function read_csv($filename){
    $file = fopen($filename, "r");

    while (($data = fgetcsv($file)) !== FALSE) {
        yield $data;
    }

    fclose($file);

}

it('Registra una venta al credito y otra al contado', function () {
    /** @var TestCase $this */

    //Venta al contado
    $data = Venta::factory([
        "moneda" => "USD",
        "precio" => "10530.96",
    ])->contado()->withReserva(false)->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    // Venta::find($id);

    $this->assertDatabaseHas("ventas", ["estado"=>1] + $data);
    $this->assertDatabaseMissing("cuotas", [
        "venta_id" => $id
    ]);
    //Idealmente se debería testear si el evento de registro de venta es disparado
    $this->assertDatabaseHas("transacciones", [
        "fecha" => $data["fecha"],
        "forma_pago" => 2,
        "moneda" => $data["moneda"],
        "importe" => $data["precio"],
    ]);
    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $data["moneda"],
        "importe" => $data["precio"],
    ]);

    //Venta al credito
    $data = Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "precio" => "10530.96",
        "plazo" => 48,
        "periodo_pago" => 1,
        "cuota_inicial" => "500",
        "tasa_interes" => "0.1000"
    ])->credito()->withReserva(false)->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $this->assertDatabaseHas("ventas", ["estado"=>1] + $data);
    foreach(read_csv(Storage::disk("tests")->path("Feature/Venta/csv/plan_pagos_1.csv")) as $row){
        $this->assertDatabaseHas("cuotas", ["vencimiento" => $row[1], "venta_id" => $id, "numero" => $row[0], "importe" => $row[2], "saldo" => $row[2], "saldo_capital" => $row[3]]);
    }

    //Idealmente se debería testear si el evento de registro de venta es disparado
    $this->assertDatabaseHas("transacciones", [
        "fecha" => $data["fecha"],
        "forma_pago" => 2,
        "moneda" => $data["moneda"],
        "importe" => $data["cuota_inicial"],
    ]);


    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $data["moneda"],
        "importe" => $data["cuota_inicial"]
    ]);
});

test("Pagos programados el 31 de cada mes", function(){
    
    //Venta al credito
    $data = Venta::factory([
        "fecha" => "2022-01-31",
        "moneda" => "USD",
        "precio" => "10530.96",
        "plazo" => 48,
        "periodo_pago" => 1,
        "cuota_inicial" => "500",
        "tasa_interes" => "0.1000"
    ])->credito()->withReserva(false)->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $this->assertDatabaseHas("ventas", ["estado"=>1] + $data);
    foreach(read_csv(Storage::disk("tests")->path("Feature/Venta/csv/plan_pagos_4.csv")) as $row){
        $this->assertDatabaseHas("cuotas", ["vencimiento" => $row[1], "venta_id" => $id, "numero" => $row[0], "importe" => $row[2], "saldo" => $row[2], "saldo_capital" => $row[3]]);
    }
});

it('Registra una venta al credito y otra al contado, pero con una reserva previa', function () {
    /** @var TestCase $this */

    //Venta al contado
    $data = Venta::factory([
        "moneda" => "USD",
        "precio" => "10530.96",
    ])->contado()->withReserva()->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    $this->assertDatabaseHas("transacciones", [
        "fecha" => $venta->fecha,
        "forma_pago" => 2,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->precio->minus($venta->reserva->importe)->amount,
    ]);
    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->precio->minus($venta->reserva->importe)->amount,
    ]);

    // Venta al credito
    $data = Venta::factory([
        "fecha" => "2020/09/15",
        "moneda" => "USD",
        "precio" => "10530.96",
        "plazo" => 48,
        "periodo_pago" => 1,
        "cuota_inicial" => "500",
        "tasa_interes" => "0.1000"
    ])->credito()->withReserva()->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    // Idealmente se debería testear si el evento de registro de venta es disparado
    $this->assertDatabaseHas("transacciones", [
        "fecha" => $data["fecha"],
        "forma_pago" => 2,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->cuota_inicial->minus($venta->reserva->importe)->amount,
    ]);


    $this->assertDatabaseHas("detalles_transaccion", [
        "transactable_id" => $id,
        "transactable_type" => Venta::class,
        "moneda" => $venta->moneda,
        "importe" => (string) $venta->cuota_inicial->minus($venta->reserva->importe)->amount,
    ]);
});

it('Convierte el importe de la reserva a la moneda de la venta y luego realiza el descuento a la cuota inicial o el precio total segun sea una venta al credito o al contado respectivamente.', function (){
    /** @var TestCase $this */

        //Venta al contado
        $data = Venta::factory([
            "moneda" => "USD",
            "precio" => "10530.96",
        ])->contado()->for(Reserva::factory([
            "moneda" => "BOB",
            "importe" => "100"
        ]))->raw();
    
        $proyectoId = $data["proyecto_id"];
    
        $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
    
        $response->assertCreated();
        $id = $response->json("id");
    
        $venta = Venta::find($id);
    
        $this->assertDatabaseHas("transacciones", [
            "fecha" => $venta->fecha,
            "forma_pago" => 2,
            "moneda" => $venta->moneda,
            "importe" => (string) $venta->precio->minus("14.51")->amount,
        ]);
        $this->assertDatabaseHas("detalles_transaccion", [
            "transactable_id" => $id,
            "transactable_type" => Venta::class,
            "moneda" => $venta->moneda,
            "importe" => (string) $venta->precio->minus("14.51")->amount,
        ]);
    
        // Venta al credito
        $data = Venta::factory([
            "fecha" => "2020/09/15",
            "moneda" => "USD",
            "precio" => "10530.96",
            "plazo" => 48,
            "periodo_pago" => 1,
            "cuota_inicial" => "500",
            "tasa_interes" => "0.1000"
        ])->credito()->for(Reserva::factory([
            "moneda" => "BOB",
            "importe" => "100"
        ]))->raw();
    
        $proyectoId = $data["proyecto_id"];
    
        $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
    
        $response->assertCreated();
        $id = $response->json("id");
    
        $venta = Venta::find($id);
    
        // Idealmente se debería testear si el evento de registro de venta es disparado
        $this->assertDatabaseHas("transacciones", [
            "fecha" => $data["fecha"],
            "forma_pago" => 2,
            "moneda" => $venta->moneda,
            "importe" => (string) $venta->cuota_inicial->minus("14.51")->amount,
        ]);
    
        $this->assertDatabaseHas("detalles_transaccion", [
            "transactable_id" => $id,
            "transactable_type" => Venta::class,
            "moneda" => $venta->moneda,
            "importe" => (string) $venta->cuota_inicial->minus("14.51")->amount,
        ]);
});

test("Un lote que ha sido reservado por un cliente no puede ser vendido a otro, a menos que la reserva haya expirado", function (){

    $lote = Lote::factory()->create();
    $now = Carbon::now();
    $reserva = Reserva::factory([
        "fecha" => $now->format("Y-m-d")
    ])->for($lote)->create();

    //Venta al contado
    $data = Venta::factory()->for($lote)->contado()->withReserva(false)->raw();

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertJsonValidationErrors([
        "lote_id" => "El lote ha sido reservado por otro cliente."
    ]);

    $this->travelTo($reserva->vencimiento);

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertJsonValidationErrors([
        "lote_id" => "El lote ha sido reservado por otro cliente."
    ]);

    $this->travel(1)->days();
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();

});