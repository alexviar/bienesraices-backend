<?php

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $proyecto = Proyecto::factory()->create();

    $response = $this->getJson("/api/proyectos/$proyecto->id/reservas");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/reservas");
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
        $rol->givePermissionTo("Ver reservas");
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

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/reservas");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver reservas");
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
        $permission->givePermissionTo("Ver reservas");
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
        $rol->givePermissionTo("Ver reservas");
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
    Reserva::factory(10)->for(Lote::factory()->for(Manzana::factory()->for($plano)))->create();
    $response = $this->actingAs(User::find(1))->getJson("/api/proyectos/$proyecto->id/reservas");

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
                "vencimiento",
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
                ]
            ]
        ]
    ]);
});

it('solo puede ver reservas del vendedor vinculado al usuario', function(){
    /** @var TestCase $this */
    $proyecto = Proyecto::factory()->create();
    $plano = Plano::factory()->for($proyecto)->create();
    Reserva::factory()->for(Lote::factory()->for(Manzana::factory()->for($plano)))->create();
    /** @var User $login */
    $login = User::factory()->create();
    $rol = Role::factory()->create();
    $rol->givePermissionTo("Ver reservas");
    $login->assignRole($rol);
    $login->vendedor()->associate(Vendedor::factory()->create());
    $reserva = Reserva::factory()
        ->for($login->vendedor)
        ->for(Lote::factory()->for(Manzana::factory()->for($plano)))
        ->create();

    $response = $this->actingAs($login)->getJson("/api/proyectos/$proyecto->id/reservas");

    $response->assertOk();
    expect($response->json("records.*.id"))->toBe([$reserva->id]);
});
