<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson('/api/roles/1');
    $response->assertUnauthorized();
});

it('verifica que el rol existe', function () {
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $response = $this->actingAs($login)->getJson("/api/roles/100");
    $response->assertNotFound();
});

#region Pruebas de autorizacion
test('usuarios sin permiso no estan autorizados', function () {
    /** @var TestCase $this */
    $rol = Role::factory()->create();
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $login->assignRole($rol);
    $response = $this->actingAs($login)->getJson("/api/roles/$rol->id");
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $rol = Role::factory()->create();
    $login = $dataset["login"];
    $response = $this->actingAs($login)->getJson("/api/roles/$rol->id");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver roles");
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
        $permission->givePermissionTo("Ver roles");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

it('obtiene un rol', function(){
    /** @var TestCase $this */

    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo(Permission::factory(2)->create());
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");
    $response = $this->actingAs($login)->getJson("/api/roles/$rol->id");
    $response->assertOk();
    $response->assertJsonStructure([
        "id",
        "name",
        "description",
        "permissions" => [
            "*" => [
                "name"
            ]
        ]
    ]);
});