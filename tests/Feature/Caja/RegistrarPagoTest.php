<?php

use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use App\Models\User;
use Tests\TestCase;

test('Los pagos exceden el monto del depósito', function () {
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
