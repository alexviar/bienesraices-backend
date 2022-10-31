<?php

use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson('/api/proyectos');
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $response = $this->actingAs($login)->getJson("/api/proyectos");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        Proyecto::factory()->create();
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
    },
    // "No vinculado" => function(){
    //     Proyecto::factory()->create();
    //     /** @var User $login */
    //     $login = User::factory([
    //         "estado" => 1
    //     ])->create();
    //     /** @var Role $rol */
    //     $rol = Role::factory()->create();
    //     $rol->givePermissionTo("Ver proyectos");
    //     $login->assignRole($rol);
    //     $login->proyectos()->attach(Proyecto::factory()->create());
    //     return [
    //         "login" => $login
    //     ];
    // }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $proyecto = $dataset["proyecto"];
    $login = $dataset["login"];

    $response = $this->actingAs($login)->getJson("/api/proyectos");
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
    // "Vinculado" => function(){
    //     $proyecto = Proyecto::factory()->create();
    //     /** @var User $login */
    //     $login = User::factory([
    //         "estado" => 1
    //     ])->create();
    //     /** @var Role $rol */
    //     $rol = Role::factory()->create();
    //     $rol->givePermissionTo("Ver proyectos");
    //     $login->assignRole($rol);
    //     $login->proyectos()->attach($proyecto);
    //     return [
    //         "proyecto" => $proyecto,
    //         "login" => $login
    //     ];
    // },
]);

test('no puede ver proyectos no vinculados', function(){
    /** @var TestCase $this */
    Proyecto::factory()->create();
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo("Ver proyectos");
    $login->assignRole($rol);
    $login->proyectos()->attach(Proyecto::factory(2)->create());
    $this->assertDatabaseCount("proyectos", 3);

    $response = $this->actingAs($login)->getJson("/api/proyectos");
    $response->assertOk();
    $response->assertJsonCount(2, "records");
    expect(collect($response->json("records"))->pluck("id")->toArray())->toBe($login->proyectos->pluck("id")->toArray());
});
#endregion


