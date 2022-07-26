<?php

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

it('registra un proyecto', function () {
    /** @var TestCase $this */
    $this->faker->seed(2022);
    $user = User::find(1);
    $data = Proyecto::factory()->raw();

    $response = $this->actingAs($user)->post('/api/proyectos', ["ubicacion"=>[
        "latitud" => $data["ubicacion"]->getLat(),
        "longitud" => $data["ubicacion"]->getLng()
    ]]+$data);
    $response->assertCreated();
    $this->assertDatabaseHas("proyectos", [
        "ubicacion"=>DB::raw("ST_GeomFromText('".$data["ubicacion"]->toWKT()."')")
    ] + $data);
    // $this->assertDatabaseHas("planos", [
    //     "proyecto_id" => $response->json("id")
    // ]);
});
