<?php

use App\Models\Credito;
use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $proyecto = Proyecto::factory()->create();

    $response = $this->getJson("/api/proyectos/$proyecto->id/ventas");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/ventas");
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
            "login" => $login, 
            "proyecto" => $proyecto
        ];
    },
    "Proyecto no vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver ventas");
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

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/ventas");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver ventas");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => Proyecto::factory()->create()
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
        $permission->givePermissionTo("Ver ventas");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => Proyecto::factory()->create()
        ];
    },
    "Proyecto vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver ventas");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);
#endregion

it('verifica la estructura de la respuesta', function () {
    /** @var TestCase $this */
    $proyecto = Proyecto::factory()->create();
    $plano = Plano::factory()->for($proyecto)->create();
    $ventas = Venta::factory(10)->for(Lote::factory()->for(Manzana::factory()->for($plano)))->credito("100")->create();
    $ventas->each(function($venta){
        Credito::factory()->for($venta, "creditable")->create();
    });
    $response = $this->actingAs(User::find(1))->getJson("/api/proyectos/$proyecto->id/ventas");

    $response->assertStatus(200);
    $response->assertJsonCount(10, "records");
    $response->assertJsonStructure([
        "meta" => [
            "total_records"
        ],
        "records" => [
            "*" => [
                "id",
                "fecha",
                "tipo",
                "proyecto_id",
                "lote_id",
                "cliente_id",
                "vendedor_id",
                "observaciones",
                "importe" => [
                    "amount",
                    "currency"
                ],
                "cliente" => [
                    "nombre_completo",
                    "documento_identidad" => [
                        "numero",
                        "tipo",
                        "tipo_text",
                    ]
                ],
                "lote" => [
                    "numero",
                    "manzana" => [
                        "numero"
                    ]
                ],
                "credito" => [
                    "id",
                    "codigo",
                    "url_plan_pago",
                    "url_historial_pagos"
                ]
            ]
        ]
    ]);
});

it('solo puede ver ventas del vendedor vinculado al usuario', function(){
    /** @var TestCase $this */
    $proyecto = Proyecto::factory()->create();
    $plano = Plano::factory()->for($proyecto)->create();
    Venta::factory()->for(Lote::factory()->for(Manzana::factory()->for($plano)))->create();
    /** @var User $login */
    $login = User::factory()->create();
    $rol = Role::factory()->create();
    $rol->givePermissionTo("Ver ventas");
    $login->assignRole($rol);
    $login->vendedor()->associate(Vendedor::factory()->create());
    $venta = Venta::factory()
        ->for($login->vendedor)
        ->for(Lote::factory()->for(Manzana::factory()->for($plano)))
        ->create();

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/reservas");

    $response->assertOk();
    expect($response->json("records.*.id"))->toBe([$venta->id]);
});
