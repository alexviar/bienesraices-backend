<?php

use App\Models\ExchangeRate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson("/api/exchange-rates");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios no autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];

    $response = $this->actingAs($login)->postJson("/api/exchange-rates");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function () {
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];

    $response = $this->actingAs($login)->postJson("/api/exchange-rates");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function () {
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar tipos de cambio");
        $login->assignRole($rol);
        return [
            "login" => $login,
        ];
    },
    "Acceso indirecto" => function () {
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Registrar tipos de cambio");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

#region Pruebas de validación
test('campos requeridos', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");

    $response = $this->actingAs($login)->postJson("/api/exchange-rates", []);
    $response->assertJsonValidationErrors([
        "valid_from" => "El campo 'válido desde' es requerido.",
        "source" => "El campo 'moneda de origen' es requerido.",
        "target" => "El campo 'moneda de destino' es requerido.",
        "rate" => "El campo 'cotización' es requerido."
    ]);
    $response->assertJsonMissingValidationErrors([
        // "end",
        "indirect"
    ]);
});

test('los tipos de cambion deben ser entre diferentes monedas', function(){
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");

    $response = $this->actingAs($login)->postJson("/api/exchange-rates", [
        "source" => "BOB",
        "target" => "BOB"
    ]);
    $response->assertJsonValidationErrors([
        "target" => "La moneda de destino y la moneda de origen deben ser diferentes.",
    ]);
});
#endregion

it("registra un tipo de cambio", function($start, $source, $target, $rate, $indirect){
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");

    $response = $this->actingAs($login)->postJson("/api/exchange-rates", array_diff([
        "valid_from" => $start,
        // "end" => $end,
        "source" => $source,
        "target" => $target,
        "rate" => $rate,
        "indirect" => $indirect
    ], [null]));
    $response->assertCreated();
    $model = ExchangeRate::find($response->json("id"));
    $attributes = $model->getAttributes();
    expect($attributes)->toMatchArray([
        "valid_from" => $start,
        // "end" => $end,
        "source" => $source,
        "target" => $target,
        "rate" => $rate,
        "indirect" => $indirect ?? false
    ]);
})->with([
    ["2020-01-01", "BOB","USD","12.345676", null],
    ["2020-01-01", "BOB","USD","12.345676", true],
]);