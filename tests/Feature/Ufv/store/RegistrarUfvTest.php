<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\UFV;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson('/api/ufvs');
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $login->assignRole($rol);
    $response = $this->actingAs($login)->postJson('/api/ufvs');
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $response = $this->actingAs($login)->postJson('/api/ufvs');
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar UFVs");
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    },
    "Acceso indirecto" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Registrar UFVs");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

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