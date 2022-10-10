<?php

use App\Models\CategoriaLote;
use App\Models\Proyecto;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

it('actualiza una categoria', function () {
    $categoria = CategoriaLote::factory()->create();
    $data = CategoriaLote::factory()->raw();
    $proyectoId = $categoria->proyecto_id;
    $response = $this->actingAs(User::find(1))->putJson("/api/proyectos/$proyectoId/categorias/$categoria->id", $data);
    $response->assertOk();
    $categoria->refresh();
    expect(Arr::only($categoria->getAttributes(), [
        "codigo",
        "precio_m2",
        "descripcion",
        "proyecto_id"
    ]))->toEqual([
        "codigo" => Str::upper($data["codigo"]),
        "precio_m2" => (string) BigDecimal::of($data["precio_m2"])->toScale(4),
        "descripcion" => Str::upper($data["descripcion"]),
        "proyecto_id" => $proyectoId
    ]);
});

test('codigos repetidos', function(){
    /** @var TestCase $this */
    [$categoria1, $categoria2] = CategoriaLote::factory(2)->for(Proyecto::factory())->create();
    $data = CategoriaLote::factory([
        "codigo" => $categoria1->codigo,
        "proyecto_id" => $categoria1->proyecto->id
    ])->raw();
    $proyectoId = $data["proyecto_id"];
    $response = $this->actingAs(User::find(1))->putJson("/api/proyectos/$proyectoId/categorias/$categoria2->id", $data);
    $response->assertJsonValidationErrors([
        "codigo" => "El código esta repetido."
    ]);
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/categorias/$categoria1->id", $data);
    $response->assertJsonMissingValidationErrors([
        "codigo" => "El código esta repetido."
    ]);
});