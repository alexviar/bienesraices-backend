<?php

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\User;
use Tests\TestCase;


test('Paginación', function () {
    /** @var TestCase $this */

    $user = User::find(1);
    $proyecto = Proyecto::factory()->create();
    $manzanas = Manzana::factory(3)->for(Plano::factory()->for($proyecto))->create();
    $manzana_ids = $this->faker->randomElements($manzanas->pluck("id")->map(function($id){
        return ["manzana_id" => $id];
    }), 5, true);
    $lotes = Lote::factory(11)->sequence(...$manzana_ids)->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/lotes?".http_build_query([
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
                "superficie",
                "precio",
                "precio_sugerido",
                "estado",
                "geocerca",
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
    $manzanas = Manzana::factory(2)->for(Plano::factory()->for($proyecto))->create();
    $manzana_ids = $this->faker->randomElements($manzanas->pluck("id")->map(function($id){
        return ["manzana_id" => $id];
    }), 5, true);
    $lotes = Lote::factory(5)->sequence(...$manzana_ids)->create();
    $response = $this->actingAs($user)->getJson("/api/proyectos/{$proyecto->id}/lotes?".http_build_query([
        "search" => $lotes[3]->numero,
    ]));
    $response->assertOk();
    $response->assertJson([
        "meta" => [ "total_records" => 1 ],
        "records" => [ $lotes[3]->toArray() ]
    ]);
    $this->assertTrue(count($response->json("records")) == 1);
});

it('verifica que el proyecto exista', function () {
    /** @var TestCase $this */

    $user = User::find(1);

    $response = $this->actingAs($user)->getJson("/api/proyectos/100/lotes");
    $response->assertNotFound();
});

it('devuelve solo los lotes que pertenecen al proyecto', function () {
    /** @var TestCase $this */

    $user = User::find(1);
    $proyecto1 = Proyecto::factory()->create();
    Manzana::factory(2)->for(Plano::factory()->for($proyecto1))->create()->each(function($manzana){
        Lote::factory(5, [
            "manzana_id" => $manzana,
        ])->create();
    });
    $proyecto2 = Proyecto::factory()->create();
    $manzanas = Manzana::factory(2)->for(Plano::factory()->for($proyecto2))->create();
    $manzana_ids = $this->faker->randomElements($manzanas->pluck("id")->map(function($id){
        return ["manzana_id" => $id];
    }), 5, true);
    $lotes = Lote::factory(5)->sequence(...$manzana_ids)->create();

    $response = $this->actingAs($user)->getJson("/api/proyectos/$proyecto2->id/lotes");
    $response->assertJson([
        "meta" => [
            "total_records" => 5,
        ],
        "records" => $lotes->sortBy(["manzana.numero", "numero"])->toArray()
    ]);
});
