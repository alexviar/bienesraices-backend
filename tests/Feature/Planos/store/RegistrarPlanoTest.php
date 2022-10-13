<?php

use App\Models\CategoriaLote;
use App\Models\Lote;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

test('campos requeridos', function () {
    $proyecto = Proyecto::factory()->create();
    $proyectoId = $proyecto->id;
    $response = $this->actingAs(User::find(1))->postJson("api/proyectos/$proyectoId/planos", []);
    $response->assertJsonValidationErrors([
        "titulo" => "El campo 'titulo' es requerido."
    ]);
});

test('solo puede haber un plano vigente', function () {
    $proyecto = Proyecto::factory()->create();
    $plano = Plano::factory()->for($proyecto)->create();
    $proyectoId = $proyecto->id;
    $response = $this->actingAs(User::find(1))->postJson("api/proyectos/$proyectoId/planos", [
        "titulo" => "Actualización 2"
    ]);
    $response->assertCreated();
    expect($plano->fresh()->is_vigente)->toBeFalse();
});

it('registra un plano vacio', function(){
    $proyecto = Proyecto::factory()->create();
    $proyectoId = $proyecto->id;
    //Aqui por ejemplo no era necesario vincular los datos al proyecto pues no es usado en el body de la solicitud
    $data = Plano::factory()->for($proyecto)->raw();
    $response = $this->actingAs(User::find(1))->postJson("api/proyectos/$proyectoId/planos", $data);
    $response->assertCreated();
    expect($proyecto->plano->getAttributes())->toMatchArray($data);
});

it('importa las manzanas y lotes desde un csv', function(){
    $proyecto = Proyecto::factory()->create();
    CategoriaLote::factory(3)->for($proyecto)->sequence(
        ["codigo" => 'A'],
        ["codigo" => 'B'],
        ["codigo" => 'C'],
    )->create();
    $proyectoId = $proyecto->id;

    //Aqui por ejemplo no era necesario vincular los datos al proyecto pues no es usado en el body de la solicitud
    $data = Plano::factory()->for($proyecto)->raw() + [
        "lotes" => UploadedFile::fake()->createWithContent(
            'lotes_test.csv',
            implode("\n", [
                "manzana,numero,superficie,categoria",
                "10,1,8984.22,B",
                "10,2,9009.33,B",
                "10,3,9014.46,B",
                "11,1,8286.89,B",
                "11,2,8100.74,C",
                "11,3,8100.74,C",
                "13,1,9920.98,B",
                "13,2,13376.86,A",
                "14,1,18566.09,A",
                "14,2,17661.62,A",
            ])
        )
    ];
    $response = $this->actingAs(User::find(1))->postJson("api/proyectos/$proyectoId/planos", $data);
    $response->assertCreated();
    $plano = $proyecto->plano;

    expect($plano->import_warnings)->toBeEmpty();

    expect($plano->manzanas->map(function($manzana){
        return implode(",",[
            $manzana->numero,
        ]);
    })->toArray())->toBe([
        "10",
        "11",
        "13",
        "14"
    ]);

    expect($plano->lotes->map(function($lote){
        return implode(",",[
            $lote->manzana->numero,
            $lote->numero,
            $lote->getAttributes()["superficie"],
            $lote->categoria->codigo
        ]);
    })->toArray())->toBe([
        "10,1,8984.22,B",
        "10,2,9009.33,B",
        "10,3,9014.46,B",
        "11,1,8286.89,B",
        "11,2,8100.74,C",
        "11,3,8100.74,C",
        "13,1,9920.98,B",
        "13,2,13376.86,A",
        "14,1,18566.09,A",
        "14,2,17661.62,A",
    ]);
});
