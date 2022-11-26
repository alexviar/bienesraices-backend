<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->getJson("/api/exchange-rates");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios no autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];

    $response = $this->actingAs($login)->getJson("/api/exchange-rates");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
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
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];

    $response = $this->actingAs($login)->getJson("/api/exchange-rates");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver tipos de cambio");
        $login->assignRole($rol);
        return [
            "login" => $login,
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
        $permission->givePermissionTo("Ver tipos de cambio");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

test('Paginación', function () {
    /** @var TestCase $this */

    $user = User::find(1);

    $response = $this->actingAs($user)->getJson("/api/exchange-rates?".http_build_query([
        "page" => [ "current" => 1, "size" => 10],
    ]));
    $response->assertOk();
    $response->assertJsonCount(2, "records");
    $response->assertJsonStructure([
        "meta" => [
            "total_records"
        ],
        "records" => [
            "*" => [
                "id",
                "valid_from",
                "source",
                "target",
                "indirect",
                "rate",
            ]
        ]
    ]);
    expect($response->json("meta.total_records"))->toBe(2);
});