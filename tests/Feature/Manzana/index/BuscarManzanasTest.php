<?php

use App\Models\Manzana;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\User;
use Tests\TestCase;

test('Paginación', function () {
    /** @var TestCase $this */

    $user = User::find(1);
    $proyecto = Proyecto::factory()->create();
    Manzana::factory(11)->for(Plano::factory()->for($proyecto))->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/manzanas?".http_build_query([
        "page" => [ "current" => 1, "size" => 10],
    ]));
    $response->assertOk();
    $response->assertJsonStructure([
        "meta" => [
            "total_records"
        ],
        "records" => [
            "*" => [
                "id",
                "numero",
                "total_lotes"
            ]
        ]
    ]);
    $this->assertTrue($response->json("meta.total_records") == 11);
    $this->assertTrue(count($response->json("records")) == 10);
});


test('Busqueda', function () {
    /** @var TestCase $this */

    $this->faker->seed(2022);
    $user = User::find(1);
    $proyecto = Proyecto::factory()->create();
    $manzanas = Manzana::factory(11)->for(Plano::factory()->for($proyecto))->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/manzanas?".http_build_query([
        "search" => $manzanas[3]->numero,
    ]));
    $response->assertOk();
    $response->assertJsonFragment([
        "id" => $manzanas[3]->id,
        "numero" => $manzanas[3]->numero,
        "total_lotes" => $manzanas[3]->total_lotes
    ]);
    $this->assertTrue($response->json("meta.total_records") == 1);
    $this->assertTrue(count($response->json("records")) == 1);
});
