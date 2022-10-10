<?php

use App\Models\CategoriaLote;
use App\Models\User;
use Tests\TestCase;

it('registra una categoria', function () {
    $data = CategoriaLote::factory()->raw();
    $proyectoId = $data["proyecto_id"];
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/categorias", $data);
    $response->assertCreated();
});

test('codigos repetidos', function(){
    /** @var TestCase $this */
    $categoria = CategoriaLote::factory()->create();
    $data = CategoriaLote::factory([
        "codigo" => $categoria->codigo,
        "proyecto_id" => $categoria->proyecto_id
    ])->raw();
    $proyectoId = $data["proyecto_id"];
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/categorias", $data);
    $response->assertJsonValidationErrors([
        "codigo" => "El código esta repetido."
    ]);
});
