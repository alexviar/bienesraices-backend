<?php

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson("/api/proyectos/100/lotes");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/lotes");
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
        $rol->givePermissionTo("Ver lotes");
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

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/lotes");
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
        $rol->givePermissionTo("Ver lotes");
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
        $permission->givePermissionTo("Ver lotes");
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
        $rol->givePermissionTo("Ver lotes");
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
    $manzanas = Manzana::factory(3)->for(Plano::factory()->for($proyecto))->create();
    $manzana_ids = $this->faker->randomElements($manzanas->pluck("id")->map(function($id){
        return ["manzana_id" => $id];
    }), 5, true);
    $lotes = Lote::factory(11)->sequence(...$manzana_ids)->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/lotes?".http_build_query([
        "page" => [ "current" => 1, "size" => 10],
    ]));
    $response->assertOk();
    $response->assertJsonCount(10, "records");
    $response->assertJsonStructure([
        "meta" => [
            "total_records"
        ],
        "records" => [
            "*" => [
                "id",
                "numero",
                "superficie",
                "precio",
                "precio_sugerido",
                "estado",
                "geocerca",
            ]
        ]
    ]);
    $this->assertTrue($response->json("meta.total_records") == 11);
    $this->assertTrue(count($response->json("records")) == 10);
});

test('Busqueda', function () {
    /** @var TestCase $this */
    $user = User::find(1);
    $proyecto = Proyecto::factory()->create();
    $manzanas = Manzana::factory(2)->for(Plano::factory()->for($proyecto))->create();
    $manzana_ids = $this->faker->randomElements($manzanas->pluck("id")->map(function($id){
        return ["manzana_id" => $id];
    }), 5, true);
    $lotes = Lote::factory(5)->sequence(...$manzana_ids)->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/lotes?".http_build_query([
        "search" => $lotes[3]->numero,
    ]));
    $response->assertOk();
    $response->assertJsonCount($lotes->where("numero", $lotes[3]->numero)->count(), "records");
    $response->assertJson([
        "meta" => [ "total_records" => $lotes->where("numero", $lotes[3]->numero)->count() ],
        "records" => $lotes->where("numero", $lotes[3]->numero)->values()->toArray()
    ]);
});

it('verifica que el proyecto exista', function () {
    /** @var TestCase $this */

    $user = User::find(1);

    $response = $this->actingAs($user)->getJson("/api/proyectos/100/lotes");
    $response->assertNotFound();
});

it('devuelve solo los lotes que pertenecen al proyecto', function () {
    /** @var TestCase $this */

    $user = User::find(1);
    $proyecto1 = Proyecto::factory()->create();
    Manzana::factory(2)->for(Plano::factory()->for($proyecto1))->create()->each(function($manzana){
        Lote::factory(5, [
            "manzana_id" => $manzana,
        ])->create();
    });
    $proyecto2 = Proyecto::factory()->create();
    $manzanas = Manzana::factory(2)->for(Plano::factory()->for($proyecto2))->create();
    $manzana_ids = $this->faker->randomElements($manzanas->pluck("id")->map(function($id){
        return ["manzana_id" => $id];
    }), 5, true);
    $lotes = Lote::factory(5)->sequence(...$manzana_ids)->create();

    $response = $this->actingAs($user)->getJson("/api/proyectos/$proyecto2->id/lotes");
    $response->assertJson([
        "meta" => [
            "total_records" => 5,
        ],
        "records" => $lotes->sortBy(["manzana.numero", "numero"])->toArray()
    ]);
});
