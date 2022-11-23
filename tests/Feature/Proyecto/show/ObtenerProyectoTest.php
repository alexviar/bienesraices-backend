<?php

use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson('/api/proyectos/100');
    $response->assertUnauthorized();
});

it('verifica que el proyecto exista', function(){
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");
    $response = $this->actingAs($login)->getJson("/api/proyectos/100");

    $response->assertNotFound();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $proyecto = $dataset["proyecto"];
    $login = $dataset["login"];
    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "proyecto" => $proyecto,
            "login" => $login
        ];
    },
    "No vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver proyectos");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "proyecto" => $proyecto,
            "login" => $login
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $proyecto = $dataset["proyecto"];
    $login = $dataset["login"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver proyectos");
        $login->assignRole($rol);
        return [
            "proyecto" => $proyecto,
            "login" => $login
        ];
    },
    "Acceso indirecto" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Ver proyectos");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "proyecto" => $proyecto,
            "login" => $login
        ];
    },
    "Vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver proyectos");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "proyecto" => $proyecto,
            "login" => $login
        ];
    },
]);
#endregion

it('verifica la estructura de la respuesta', function(){
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");
    $proyecto = Proyecto::factory()->create();
    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id");
    $response->assertJsonStructure([
        "id",
        "nombre",
        "currency" => [
            "code",
            "name"
        ]
    ]);
});