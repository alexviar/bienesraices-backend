<?php

use App\Models\UFV;
use App\Models\User;
use Tests\TestCase;

it('registra una ufv', function () {
    /** @var TestCase $this */
    $data = [
        "fecha" => "2022-08-29",
        "valor" => "2.23456"
    ];
    $response = $this->actingAs(User::find(1))->postJson("api/ufvs", $data);
    $response->assertCreated();

    $this->assertDatabaseHas("ufv", $data);
});

test('solo un registro por fecha', function () {
    /** @var TestCase $this */
    $data = [
        "fecha" => "2022-08-29",
        "valor" => "2.23456"
    ];
    UFV::create($data);
    $response = $this->actingAs(User::find(1))->postJson("api/ufvs", $data);
    $response->assertJsonValidationErrors([
        "fecha" => "Ya existe un registro en la fecha indicada."
    ]);

    $this->assertDatabaseHas("ufv", $data);
});

it('registra el valor con 5 decimales', function () {
    /** @var TestCase $this */
    $data = [
        "fecha" => "2022-08-29",
        "valor" => "2.234565"
    ];
    $response = $this->actingAs(User::find(1))->postJson("api/ufvs", $data);

    $this->assertDatabaseHas("ufv", [ "valor" => "2.23457" ] + $data);
});