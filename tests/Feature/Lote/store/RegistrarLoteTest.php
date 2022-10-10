<?php

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\User;
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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

test("Proyecto no existe", function(){
    /** @var TestCase $this */

    $user = User::find(1);

    $response = $this->actingAs($user)->postJson("/api/proyectos/100/lotes", []);
    $response->assertNotFound();
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