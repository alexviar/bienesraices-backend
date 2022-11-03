<?php

use App\Models\Manzana;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson("/api/proyectos/100/manzanas");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/manzanas");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "login" => $login, 
            "proyecto" => $proyecto
        ];
    },
    "Proyecto no vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver manzanas");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/manzanas");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver manzanas");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Acceso indirecto" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Ver manzanas");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Proyecto vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver manzanas");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);
#endregion

test('Paginación', function () {
    /** @var TestCase $this */

    $user = User::find(1);
    $proyecto = Proyecto::factory()->create();
    Manzana::factory(11)->for(Plano::factory()->for($proyecto))->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/manzanas?".http_build_query([
        "page" => [ "current" => 1, "size" => 10],
    ]));
    $response->assertOk();
    $response->assertJsonStructure([
        "meta" => [
            "total_records"
        ],
        "records" => [
            "*" => [
                "id",
                "numero",
                "total_lotes"
            ]
        ]
    ]);
    $this->assertTrue($response->json("meta.total_records") == 11);
    $this->assertTrue(count($response->json("records")) == 10);
});


test('Busqueda', function () {
    /** @var TestCase $this */

    $this->faker->seed(2022);
    $user = User::find(1);
    $proyecto = Proyecto::factory()->create();
    $manzanas = Manzana::factory(11)->for(Plano::factory()->for($proyecto))->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/manzanas?".http_build_query([
        "search" => $manzanas[3]->numero,
    ]));
    $response->assertOk();
    $response->assertJsonFragment([
        "id" => $manzanas[3]->id,
        "numero" => $manzanas[3]->numero,
        "total_lotes" => $manzanas[3]->total_lotes
    ]);
    $this->assertTrue($response->json("meta.total_records") == 1);
    $this->assertTrue(count($response->json("records")) == 1);
});
