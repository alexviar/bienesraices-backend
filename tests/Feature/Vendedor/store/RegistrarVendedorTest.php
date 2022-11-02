<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson('/api/vendedores');
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
    $response = $this->actingAs($login)->postJson('/api/vendedores');
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $response = $this->actingAs($login)->postJson('/api/vendedores');
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar vendedores");
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
        $permission->givePermissionTo("Registrar vendedores");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

it('registra un vendedor', function(){
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $rol = Role::factory()->create();
    $rol->givePermissionTo("Registrar vendedores");
    $login->assignRole($rol);

    $data = Vendedor::factory()->raw();

    $response = $this->actingAs($login)->postJson("/api/vendedores", $data);
    $id = $response->json("id");
    $vendedor = Vendedor::find($id);
    expect(Arr::only($vendedor->getAttributes(), [
        "numero_documento",
        "apellido_materno",
        "apellido_paterno",
        "nombre",
        "telefono"
    ]))->toEqual([
        "numero_documento" => Str::upper($data["numero_documento"]),
        "apellido_materno" => Str::upper($data["apellido_materno"]),
        "apellido_paterno" => Str::upper($data["apellido_paterno"]),
        "nombre" => Str::upper($data["nombre"]),
        "telefono" => $data["telefono"]
    ]);
});