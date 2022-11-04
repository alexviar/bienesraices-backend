<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson('/api/roles');
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
    $response = $this->actingAs($login)->postJson('/api/roles');
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $response = $this->actingAs($login)->postJson('/api/roles');
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar roles");
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
        $permission->givePermissionTo("Registrar roles");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

#region Pruebas de validación
test("datos requeridos", function (){
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");

    $response = $this->actingAs($login)->postJson("/api/roles", [
        "permissions" => []
    ]);
    $response->assertJsonValidationErrors([
        "name"=> "El campo 'nombre' es requerido.",
        "permissions" => "Debe asignar al menos un permiso."
    ]);
    $response->assertJsonMissingValidationErrors([
        "description",
    ]);
});

test("permisos inexistentes", function(){
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");

    $response = $this->actingAs($login)->postJson("/api/roles", [
        "permissions" => ["Permiso inexistente"]
    ]);
    $response->assertJsonValidationErrors([
        "permissions.0" => "El permiso seleccionado es inválido."
    ]);
});
#endregion

it('registra un rol', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");

    Permission::factory(2)->sequence([
        "name" => "Permiso de prueba 1"
    ],[
        "name" => "Permiso de prueba 2"
    ])->create();

    $response = $this->actingAs($login)->postJson("/api/roles", [
        "name" => "Rol de prueba",
        "description" => "Este es un rol de prueba",
        "permissions" => ["Permiso de prueba 1", "Permiso de prueba 2"]
    ]);
    $response->assertCreated();
    $rol = Role::findById($response->json("id"));
    expect(Arr::only($rol->getAttributes(), [
        "name", "description"
    ]))->toBe([
        "name" => "Rol de prueba",
        "description" => "Este es un rol de prueba",
    ]);
    expect($rol->hasAllPermissions("Permiso de prueba 1", "Permiso de prueba 2"))->toBeTrue();
});