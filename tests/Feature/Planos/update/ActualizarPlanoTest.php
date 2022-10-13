<?php

use App\Models\CategoriaLote;
use App\Models\Lote;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

test('campos requeridos', function () {
    $plano = Plano::factory()->for(Proyecto::factory())->create();
    $response = $this->actingAs(User::find(1))->putJson("api/proyectos/$plano->proyecto_id/planos/$plano->id", []);
    $response->assertJsonValidationErrors([
        "titulo" => "El campo 'titulo' es requerido."
    ]);
});

it('actualiza un plano', function(){
    $proyecto = Proyecto::factory()->create();
    $plano = Plano::factory()->for($proyecto)->create();
    //Aqui por ejemplo no era necesario vincular los datos al proyecto pues no es usado en el body de la solicitud
    $data = Plano::factory()->for($proyecto)->raw();
    $response = $this->actingAs(User::find(1))->putJson("api/proyectos/$proyecto->id/planos/$plano->id", $data);
    $response->assertOk();
    expect($plano->fresh()->getAttributes())->toMatchArray($data);
});

it('importa las manzanas y lotes desde un csv', function(){
    $proyecto = Proyecto::factory()->create();
    CategoriaLote::factory(3)->for($proyecto)->sequence(
        ["codigo" => 'A'],
        ["codigo" => 'B'],
        ["codigo" => 'C'],
    )->create();
    $plano = Plano::factory()->for($proyecto)->create();
    $tmpfname = tempnam(sys_get_temp_dir(), 'lotes.csv');
    file_put_contents($tmpfname, implode("\n", [
        "manzana,numero,superficie,categoria",
        "10,1,18984.22,B",
        "10,2,19009.33,B",
        "10,3,19014.46,B",
        "11,1,18286.89,B",
        "11,2,18100.74,C",
        "11,3,18100.74,C",
        "13,1,19920.98,B",
        "13,2,113376.86,A",
        "14,1,118566.09,A",
        "14,2,117661.62,AF",
    ]));
    $plano->importManzanasYLotesFromCsv($tmpfname);
    $plano->refresh();

    expect($plano->manzanas)->toHaveCount(4);
    expect($plano->lotes)->toHaveCount(9);
    expect($plano->hasErrors)->toBeTrue();

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
    $response = $this->actingAs(User::find(1))->putJson("api/proyectos/$proyecto->id/planos/$plano->id", $data);
    $response->assertOk();
    $plano = $plano->fresh();

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
        "10,1,18984.22,B",
        "10,2,19009.33,B",
        "10,3,19014.46,B",
        "11,1,18286.89,B",
        "11,2,18100.74,C",
        "11,3,18100.74,C",
        "13,1,19920.98,B",
        "13,2,113376.86,A",
        "14,1,118566.09,A",
        "14,2,17661.62,A",
    ]);
});
