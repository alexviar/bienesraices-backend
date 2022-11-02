<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson('/api/vendedores');
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
    $response = $this->actingAs($login)->getJson('/api/vendedores');
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $response = $this->actingAs($login)->getJson('/api/vendedores');
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver vendedores");
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
        $permission->givePermissionTo("Ver vendedores");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

it('solo muestra el vendedor al que esta vinculado el usuario', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $rol = Role::factory()->create();
    $rol->givePermissionTo("Ver vendedores");
    $login->assignRole($rol);
    $login->vendedor()->associate(Vendedor::factory()->create());
    Vendedor::factory()->create();
    expect(Vendedor::count())->not->toBe(1);

    $response = $this->actingAs($login)->getJson('/api/vendedores');
    expect($response->json("records.*.id"))->toBe([$login->vendedor_id]);
});