<?php

use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;

test('el usuario ha iniciado sesión', function () {
    $plano = Plano::factory()->create();

    $response = $this->getJson("/api/proyectos/$plano->proyecto_id/planos/$plano->id");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $plano = $dataset["plano"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$plano->proyecto_id/planos/$plano->id");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $plano = Plano::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "login" => $login, 
            "plano" => $plano
        ];
    },
    "Proyecto no vinculado" => function(){
        $plano = Plano::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver planos");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "plano" => $plano
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $plano = $dataset["plano"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$plano->proyecto_id/planos/$plano->id");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver planos");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "plano" => Plano::factory()->create()
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
        $permission->givePermissionTo("Ver planos");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "plano" => Plano::factory()->create()
        ];
    },
    "Proyecto vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        $plano = Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver planos");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "plano" => $plano
        ];
    }
]);
#endregion
