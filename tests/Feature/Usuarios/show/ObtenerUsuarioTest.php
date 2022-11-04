<?php

use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson('/api/usuarios/1');
    $response->assertUnauthorized();
});

it('verifica que el rol existe', function () {
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $response = $this->actingAs($login)->getJson("/api/usuarios/100");
    $response->assertNotFound();
});

#region Pruebas de autorizacion
test('usuarios sin permiso no estan autorizados', function () {
    /** @var TestCase $this */
    $usuario = User::factory()->create();
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $login->assignRole($rol);
    $response = $this->actingAs($login)->getJson("/api/usuarios/$usuario->id");
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $usuario = User::factory()->create();
    $login = $dataset["login"];
    $response = $this->actingAs($login)->getJson("/api/usuarios/$usuario->id");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver usuarios");
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
        $permission->givePermissionTo("Ver usuarios");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

it('obtiene información del usuario', function(){
    /** @var TestCase $this */

    $usuario = User::factory()->create();
    $usuario->assignRole(Role::factory()->create());
    $usuario->vendedor()->associate(Vendedor::factory()->create());
    $usuario->proyectos()->attach(Proyecto::factory()->create());
    $usuario->save();
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");
    $response = $this->actingAs($login)->getJson("/api/usuarios/$usuario->id");
    $response->assertOk();
    $response->assertJsonStructure([
        "id",
        "username",
        "email",
        "vendedor" => [
            "id",
            "nombre_completo"
        ],
        "proyectos" => [
            "*" => [
                "id",
                "nombre"
            ]
        ],
        "roles" => [
            "*" => [
                "id",
                "name"
            ]
        ]
    ]);
});