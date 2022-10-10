<?php

use App\Models\Manzana;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\User;
use Tests\TestCase;

it("registra una manzana", function(){
    /** @var TestCase $this */

    $user = User::find(1);
    $plano = Plano::factory()->create();
    $data = Manzana::factory()->for($plano)->raw();
    $proyecto_id = $plano->proyecto_id;

    $response = $this->actingAs($user)->postJson("/api/proyectos/$proyecto_id/manzanas", $data);
    $response->assertCreated();

    $this->assertDatabaseHas("manzanas", $data);
});

test("Campos requeridos", function(){
    /** @var TestCase $this */

    $user = User::find(1);
    $plano = Plano::factory()->create();
    $data = Manzana::factory()->for($plano)->raw();
    $proyecto_id = $plano->proyecto_id;

    $response = $this->actingAs($user)->postJson("/api/proyectos/{$proyecto_id}/manzanas", []);
    $response->assertJsonValidationErrors([
        "numero" => "El campo 'número' es requerido."
    ]);
});

test("Proyecto no existe", function(){
    /** @var TestCase $this */

    $user = User::find(1);

    $response = $this->actingAs($user)->postJson("/api/proyectos/100/manzanas", []);
    $response->assertNotFound();
});

test("Numero repetido", function(){
    /** @var TestCase $this */

    $user = User::find(1);
    $manzana = Manzana::factory()->create();

    $response = $this->actingAs($user)->postJson("/api/proyectos/{$manzana->proyecto->id}/manzanas", [
        "numero" => $manzana->numero
    ]);
    $response->assertJsonValidationErrors([
        "numero" => "Ya ha registrado una manzana con el mismo número"
    ]);
});