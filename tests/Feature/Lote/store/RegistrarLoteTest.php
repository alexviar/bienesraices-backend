<?php

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson("/api/proyectos/100/lotes");
    $response->assertUnauthorized();
});

test('not found', function($dataset){
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/lotes");
    $response->assertNotFound();
})->with([
    "Proyecto inexistente" => function(){
        $proyecto = new Proyecto();
        $proyecto->id = 100;
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Plano obsoleto" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "proyecto" => Plano::factory([
                "estado" => 0 //Obsoleto y Editable
            ])->create()->proyecto
        ];
    },
]);

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/lotes");
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
    "Plano vigente no editable" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar lotes");
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "proyecto" => Plano::factory([
                "estado" => 3 //Vigente y No editable
            ])->create()->proyecto
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
        $rol->givePermissionTo("Registrar lotes");
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

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/planos");
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
        $rol->givePermissionTo("Registrar planos");
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
        $permission->givePermissionTo("Registrar planos");
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
        $rol->givePermissionTo("Registrar planos");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);
#endregion

it('registra un lotes', function () {
    /** @var TestCase $this */

    $user = User::find(1);
    $manzana = Manzana::factory()->create();
    $data = Lote::factory([
        "geocerca" => new Polygon([
            new LineString([
                new Point(40.74894149554006, -73.98615270853043),
                new Point(40.74848633046773, -73.98648262023926),
                new Point(40.747925497790725, -73.9851602911949),
                new Point(40.74837050671544, -73.98482501506805),
                new Point(40.74894149554006, -73.98615270853043)
            ])
        ])
    ])->for($manzana)->raw();
    $proyecto_id = $manzana->proyecto->id;
    $data["geocerca"] = $data["geocerca"]->toWKT();

    $response = $this->actingAs($user)->postJson("/api/proyectos/$proyecto_id/lotes", $data);
    $response->assertCreated();

    $this->assertDatabaseHas("lotes", [
        "geocerca"=>DB::raw("ST_GeomFromText('".$data["geocerca"]."')")
    ] + $data);
});

it('valida los campos requeridos', function () {
    /** @var TestCase $this */

    $user = User::find(1);
    $manzana = Manzana::factory()->create();
    $proyecto_id = $manzana->proyecto->id;

    $response = $this->actingAs($user)->postJson("/api/proyectos/$proyecto_id/lotes", []);
    $response->assertJsonValidationErrors([
        "numero" => "El campo 'número' es requerido.",
        "manzana_id" => "El campo 'manzana' es requerido.",
        "superficie" => "El campo 'superficie' es requerido.",
        "geocerca" => "El campo 'geocerca' es requerido."
    ]);
});

test("Número repetido", function(){
    /** @var TestCase $this */

    $user = User::find(1);
    $manzana = Manzana::factory()->create();
    $loteExistente = Lote::factory([
        "manzana_id" => $manzana->id,
        "geocerca" => new Polygon([
            new LineString([
                new Point(40.74894149554006, -73.98615270853043),
                new Point(40.74848633046773, -73.98648262023926),
                new Point(40.747925497790725, -73.9851602911949),
                new Point(40.74837050671544, -73.98482501506805),
                new Point(40.74894149554006, -73.98615270853043)
            ])
        ])
    ])->create();

    $response = $this->actingAs($user)->postJson("/api/proyectos/{$manzana->proyecto->id}/lotes", [
        "numero" => $loteExistente->numero,
        "manzana_id" => $loteExistente->manzana_id
    ]);
    $response->assertJsonValidationErrors([
        "numero" => "La manzana indicada tiene un lote con el mismo número."
    ]);
});

test("Lotes que se sobreponen", function(){
    /** @var TestCase $this */

    $user = User::find(1);
    $manzana = Manzana::factory()->create();
    $loteExistente = Lote::factory([
        "manzana_id" => $manzana->id,
        "geocerca" => new Polygon([
            new LineString([
                new Point(1, -1),
                new Point(1, 1),
                new Point(-1, 1),
                new Point(-1, -1),
                new Point(1, -1)
            ])
        ])
    ])->create();
    $data = Lote::factory([
        "manzana_id" => $manzana->id,
        "geocerca" => new Polygon([
            new LineString([
                new Point(3, -3),
                new Point(3, -0.99999999999999),
                new Point(0.99999999999999, -0.99999999999999),
                new Point(0.99999999999999, -3),
                new Point(3, -3)
            ])
        ])
    ])->raw();
    $data["geocerca"] = $data["geocerca"]->toWKT();

    $response = $this->actingAs($user)->postJson("/api/proyectos/{$manzana->proyecto->id}/lotes", $data);
    $response->assertJsonValidationErrors([
        "geocerca" => "La geocerca se sobrepone con otros lotes."
    ]);
});