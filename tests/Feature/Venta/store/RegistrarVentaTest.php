<?php

use App\Models\Credito;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

require(__DIR__."/assertTransaccion.php");

function read_csv($filename){
    $file = fopen($filename, "r");

    while (($data = fgetcsv($file, 0, "\t")) !== FALSE) {
        yield $data;
    }

    fclose($file);

}

test('La fecha no puede estar en el futuro', function(){
    /** @var TestCase $this */

    $proyecto = Proyecto::factory()->create();


    $today = Carbon::today();
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyecto->id/ventas", [
        "fecha" => $today->clone()->addDay()->format("Y-m-d")
    ]);
    $response->assertJsonValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyecto->id/ventas", [
        "fecha" => $today->format("Y-m-d")
    ]);
    $response->assertJsonMissingValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);
});

it('Registra una venta al contado', function () {
    /** @var TestCase $this */

    //Venta al contado
    $data = Venta::factory([
        "moneda" => "USD",
        "importe" => "10530.96",
    ])->contado()->withReserva(false)->raw();

    $data += [
        "pago" => [
            "moneda" => "USD",
            "monto" => "10530.96",
            "numero_transaccion" => "12423325848",
            "comprobante" => UploadedFile::fake()->image("comprobante.png")
        ]
    ];

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    $this->assertSame(1, $venta->estado);
    $this->assertSame(1, $venta->tipo);
    $keys = [
        "fecha",
        "moneda",
        "proyecto_id",
        "lote_id",
        "cliente_id",
        "vendedor_id",
    ];
    $this->assertEquals(Arr::only($data, $keys), Arr::only($venta->getAttributes(), $keys));
    $this->assertEquals((string) BigDecimal::of($data["importe"])->toScale(4), (string) $venta->importe->amount);

    assertTransaccionPorVentaAlContado($data, $venta, "10530.96");
});

it("Registra una venta al crédito", function(){
    $data = Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "10530.96",
    ])->credito()->withReserva(false)->raw();
    $dataCredito = Credito::factory([
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1,
    ])->raw();
    $data += [
        "credito" => $dataCredito
    ] + [
        "pago" => [
            "moneda" => "USD",
            "monto" => "500",
            "numero_transaccion" => "1242325848",
            "comprobante" => UploadedFile::fake()->image("comprobante.png")
        ]
    ];

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    $this->assertSame(1, $venta->estado);
    $this->assertSame(2, $venta->tipo);
    $keys = [
        "fecha",
        "moneda",
        "proyecto_id",
        "lote_id",
        "cliente_id",
        "vendedor_id",
    ];
    $this->assertEquals(Arr::only($data, $keys), Arr::only($venta->getAttributes(), $keys));
    $this->assertEquals((string) BigDecimal::of($data["importe"])->toScale(4), (string) $venta->importe->amount);
    $dataCredito["cuota_inicial"] = (string) BigDecimal::of($dataCredito["cuota_inicial"])->toScale(4);
    expect($venta->credito->getAttributes())->toMatchArray(Arr::except($dataCredito, ["creditable_id", "creditable_type"]));

    assertTransaccionPorVentaAlCredito($data, $venta->credito, "500");
    
    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk("tests");
    foreach(read_csv($disk->path("Feature/Venta/csv/plan_pagos_1.csv")) as $row){
        $this->assertDatabaseHas("cuotas", ["vencimiento" => $row[1], "credito_id" => $venta->credito->id, "numero" => $row[0], "importe" => $row[3], "saldo" => $row[3], "saldo_capital" => $row[6]]);
    }
});

test("Pagos programados el 31 de cada mes", function(){
    $data = Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "10530.96",
    ])->credito()->withReserva(false)->raw();
    $dataCredito = Credito::factory([
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 31,
    ])->raw();
    $data += [
        "credito" => $dataCredito
    ] + [
        "pago" => [
            "moneda" => "USD",
            "monto" => "500",
            "numero_transaccion" => "1242325848",
            "comprobante" => UploadedFile::fake()->image("comprobante.png")
        ]
    ];

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    $this->assertSame(1, $venta->estado);
    $this->assertSame(2, $venta->tipo);
    $keys = [
        "fecha",
        "moneda",
        "proyecto_id",
        "lote_id",
        "cliente_id",
        "vendedor_id",
    ];
    $this->assertEquals(Arr::only($data, $keys), Arr::only($venta->getAttributes(), $keys));
    $this->assertEquals((string) BigDecimal::of($data["importe"])->toScale(4), (string) $venta->importe->amount);
    
    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk("tests");
    foreach(read_csv($disk->path("Feature/Venta/csv/plan_pagos_4.csv")) as $row){
        $this->assertDatabaseHas("cuotas", ["vencimiento" => $row[1], "credito_id" => $venta->credito->id, "numero" => $row[0], "importe" => $row[3], "saldo" => $row[3], "saldo_capital" => $row[6]]);
    }
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

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
        "pago" => [
            "moneda" => $data["moneda"],
            "monto" => $data["importe"],
            "numero_transaccion" => "1242325848",
            "comprobante" => UploadedFile::fake()->image("comprobante.png")
        ]
    ]);

    $response->assertJsonValidationErrors([
        "lote_id" => "El lote ha sido reservado por otro cliente."
    ]);

    $this->travelTo($reserva->vencimiento);

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
        "pago" => [
            "moneda" => $data["moneda"],
            "monto" => $data["importe"],
            "numero_transaccion" => "1242325848",
            "comprobante" => UploadedFile::fake()->image("comprobante.png")
        ]
    ]);

    $response->assertJsonValidationErrors([
        "lote_id" => "El lote ha sido reservado por otro cliente."
    ]);

    $this->travel(1)->days();
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data + [
        "pago" => [
            "moneda" => $data["moneda"],
            "monto" => $data["importe"],
            "numero_transaccion" => "1242325848",
            "comprobante" => UploadedFile::fake()->image("comprobante.png")
        ]
    ]);

    $response->assertCreated();

});