<?php

use App\Models\Cuota;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;


test('La fecha no puede estar en el futuro', function(){
    /** @var TestCase $this */

    $today = Carbon::today();
    $response = $this->actingAs(User::find(1))->postJson("/api/transacciones", [
        "fecha" => $today->clone()->addDay()->format("Y-m-d")
    ]);
    $response->assertJsonValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);

    $response = $this->actingAs(User::find(1))->postJson("/api/transacciones", [
        "fecha" => $today->format("Y-m-d")
    ]);
    $response->assertJsonMissingValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);
});

it('No permite pagos que excedan el monto del depósito', function () {
    /** @var TestCase $this */

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "importe" => "100",
        "detalles" => [
            [ "importe" => "50" ],
            [ "importe" => "50.01" ]
        ],
    ]);

    $response->assertJsonValidationErrors([
        "detalles" => "Los pagos exceden el monto depositado (Pagos: 100.01, Deposito: 100.00)"
    ]);
});

it('Registra un deposito', function() {
    /** @var TestCase $this */

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-04-28"));

    $venta = Venta::factory([
        "fecha" => now()->subMonthNoOverflow(2)->subDays(5),
    ])->credito()->create();
    $venta->crearPlanPago();

    $this->travelTo($venta->cuotas[1]->vencimiento);
    $this->travel(2)->days();

    $data = Transaccion::factory()->raw([
        "fecha" => "2022-07-20", //vencimiento de la segunda cuota
        "importe" => "100",
    ]);

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "detalles" => [
            [
                "importe" => "50",
                "transactable_id" => $venta->cuotas[0]->id,
                "transactable_type" => Cuota::class
            ],
            [
                "importe" => "50.0",
                "transactable_id" => $venta->cuotas[1]->id,
                "transactable_type" => Cuota::class
            ]
        ],
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ]+$data);

    $response->assertCreated();

    $this->assertDatabaseHas("transacciones", [
        "fecha" => "2022-07-20"
    ]);
});

it('Registra un deposito con fecha implicita', function() {
    /** @var TestCase $this */

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-04-28"));

    $venta = Venta::factory([
        "fecha" => now()->subMonthNoOverflow(2)->subDays(5),
    ])->credito()->create();
    $venta->crearPlanPago();

    $data = Transaccion::factory()->raw([
        "importe" => "100",
    ]);
    unset($data["fecha"]);

    $this->travelTo($venta->cuotas[1]->vencimiento);
    $this->travel(2)->days();

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "detalles" => [
            [
                "importe" => "50",
                "transactable_id" => $venta->cuotas[0]->id,
                "transactable_type" => Cuota::class
            ],
            [
                "importe" => "50.0",
                "transactable_id" => $venta->cuotas[1]->id,
                "transactable_type" => Cuota::class
            ]
        ],
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ]+$data);

    $response->assertCreated();

    $this->assertDatabaseHas("transacciones", [
        "fecha" => "2022-07-22" //Dos dias despues del vencimiento de la segunda cuota (Ahora)
    ]);
});


test('Solo puede pagar cuotas en curso o vencidas', function() {
    /** @var TestCase $this */

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-04-28"));

    $venta = Venta::factory([
        "fecha" => now()->subMonthNoOverflow(2)->subDays(5),
    ])->credito()->create();
    $venta->crearPlanPago();

    $data = Transaccion::factory()->raw([
        "importe" => "100",
    ]);
    unset($data["fecha"]);

    $this->travelTo($venta->cuotas[0]->vencimiento);

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "detalles" => [
            [
                "importe" => "50",
                "transactable_id" => $venta->cuotas[0]->id,
                "transactable_type" => Cuota::class
            ],
            [
                "importe" => "50.0",
                "transactable_id" => $venta->cuotas[1]->id,
                "transactable_type" => Cuota::class
            ]
        ],
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ]+$data);

    $response->assertJsonValidationErrors([
        "detalles.1.transactable_id" => "Solo puede pagar cuotas en curso o vencidas"
    ]);
});



test('El importe debe ser menor o igual al saldo de la cuota', function() {
    /** @var TestCase $this */

    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2022-04-28"));

    $venta = Venta::factory([
        "fecha" => now()->subMonthNoOverflow(2)->subDays(5),
    ])->credito()->create();
    $venta->crearPlanPago();

    $data = Transaccion::factory()->raw([
        "importe" => "719",
    ]);
    unset($data["fecha"]);

    $fecha = $venta->cuotas[1]->vencimiento->clone()->addDays(5);

    $this->travelTo($fecha);

    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "detalles" => [
            [
                "importe" => "50",
                "transactable_id" => $venta->cuotas[0]->id,
                "transactable_type" => Cuota::class
            ],
            [
                "importe" => "668.73",
                "transactable_id" => $venta->cuotas[1]->id,
                "transactable_type" => Cuota::class
            ]
        ],
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ]+$data);

    $response->assertJsonValidationErrors([
        "detalles.1" => "El importe debe ser menor o igual al saldo de la cuota."
    ]);
    
    $response = $this->actingAs(User::find(1))->postJson('/api/transacciones', [
        "detalles" => [
            [
                "importe" => "50",
                "transactable_id" => $venta->cuotas[0]->id,
                "transactable_type" => Cuota::class
            ],
            [
                "importe" => "668.72",
                "transactable_id" => $venta->cuotas[1]->id,
                "transactable_type" => Cuota::class
            ]
        ],
        "comprobante" => UploadedFile::fake()->image("comprobante.png")
    ]+$data);
    $response->assertCreated();
});
