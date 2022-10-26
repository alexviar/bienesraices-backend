<?php

use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;

test('El usuario ha iniciado sesión', function () {
    /** @var TestCase $this */

    $response = $this->getJson("/api/usuarios", []);
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('Usuarios bloqueados no tienen autorizacion', function () {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory([
        "estado" => 0
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Ver usuarios"
    ]);
    $login->assignRole($rol);

    $response = $this->actingAs($login)->getJson("/api/usuarios", []);
    $response->assertForbidden();
});

test('Usuarios sin permisos no tienen autorizacion', function () {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();

    $response = $this->actingAs($login)->getJson("/api/usuarios", []);
    $response->assertForbidden();
});
#endregion

test('Paginación', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");
    $users = User::factory(11)->create();
    $response = $this->actingAs($login)->getJson("/api/usuarios?".http_build_query([
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
                "username",
                "email",
                "estado",
                "estado_text"
            ]
        ]
    ]);
    $this->assertTrue($response->json("meta.total_records") == 13);
    $this->assertTrue(count($response->json("records")) == 10);
});

// test('Busqueda', function () {
//     /** @var TestCase $this */
//     $user = User::find(1);
//     $proyecto = Proyecto::factory()->create();
//     $manzanas = Manzana::factory(2)->for(Plano::factory()->for($proyecto))->create();
//     $manzana_ids = $this->faker->randomElements($manzanas->pluck("id")->map(function($id){
//         return ["manzana_id" => $id];
//     }), 5, true);
//     $lotes = Lote::factory(5)->sequence(...$manzana_ids)->create();
//     $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/lotes?".http_build_query([
//         "search" => $lotes[3]->numero,
//     ]));
//     $response->assertOk();
//     $response->assertJsonCount($lotes->where("numero", $lotes[3]->numero)->count(), "records");
//     $response->assertJson([
//         "meta" => [ "total_records" => $lotes->where("numero", $lotes[3]->numero)->count() ],
//         "records" => $lotes->where("numero", $lotes[3]->numero)->values()->toArray()
//     ]);
// });

it('busca usuarios usando diferentes formas de autorización', function($dataset) {
    /** @var TestCase $this */

    $login = $dataset["login"];

    $response = $this->actingAs($login)->getJson("/api/usuarios");
    $response->assertOk();
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory()->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo([
            "Ver usuarios"
        ]);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    },
    "Acceso indirecto" => function(){
        /** @var User $login */
        $login = User::factory()->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo([
            "Ver usuarios"
        ]);
        $rol->givePermissionTo([
            $permission
        ]);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    },
    // "Super usuario" => function(){
    //     /** @var User $login */
    //     $login = User::factory()->create();
    //     $login->assignRole("Super usuarios");
    //     return [
    //         "login" => $login
    //     ];
    // }
]);
