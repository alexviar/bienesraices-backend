<?php

use App\Models\Proyecto;
use App\Models\User;
use Brick\Math\BigDecimal;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

it('verifica que el proyecto exista', function(){
    $response = $this->actingAs(User::find(1))->putJson("/api/proyectos/1", []);

    $response->assertNotFound();
});

it('los campos solo se validan cuando estan presentes', function () {
    /** @var TestCase $this */
    $proyecto = Proyecto::factory()->create();

    $response = $this->actingAs(User::find(1))->putJson("/api/proyectos/$proyecto->id", []);

    $response->assertOk();
});

it('actualiza el proyecto parcialmente', function ($dataset) {
    /** @var TestCase $this */
    $proyecto = $dataset["proyecto"];
    $data = $dataset["data"];
    $expectation = $dataset["expectations"];
    $expectation["nombre"] = Str::upper($expectation["nombre"]);
    $expectation["precio_reservas"] = (string) BigDecimal::of($expectation["precio_reservas"])->toScale(4);
    $expectation["cuota_inicial"] = (string) BigDecimal::of($expectation["cuota_inicial"])->toScale(4);
    $expectation["tasa_interes"] = (string) BigDecimal::of($expectation["tasa_interes"])->toScale(4);
    $expectation["tasa_mora"] = (string) BigDecimal::of($expectation["tasa_mora"])->toScale(4);
    if(is_array($expectation["ubicacion"]))
        $expectation["ubicacion"] = new Point($expectation["ubicacion"]["latitud"], $expectation["ubicacion"]["longitud"]);

    $response = $this->actingAs(User::find(1))->putJson("/api/proyectos/$proyecto->id", $data);

    $response->assertOk();
    $proyecto->refresh();
    expect(Arr::except($proyecto->getAttributes(), [
        "id",
        "legacy_id",
        "created_at",
        "updated_at",
    ]))->toEqual($expectation);

})->with([
   function()
   {
    $proyecto = Proyecto::factory()->create();
    $data = Proyecto::factory()->raw();
    $data["ubicacion"] = [
        "latitud" => $data["ubicacion"]->getLat(),
        "longitud" => $data["ubicacion"]->getLng()
    ];
    return [
        "proyecto" => $proyecto,
        "data" => $data,
        "expectations" => $data
    ];
   },
   function()
   {
    $proyecto = Proyecto::factory()->create();
    $data = Proyecto::factory()->raw();
    $data["ubicacion"] = [
        "latitud" => $data["ubicacion"]->getLat(),
        "longitud" => $data["ubicacion"]->getLng()
    ];
    return [
        "proyecto" => $proyecto,
        "data" => Arr::only($data, [
            "nombre"
        ]),
        "expectations" => Arr::only($data, [
            "nombre"
        ]) + Arr::except($proyecto->getAttributes(), [
            "id",
            "legacy_id",
            "created_at",
            "updated_at",
            "nombre"
        ])
    ];
   }  
]);
