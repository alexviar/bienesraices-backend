<?php

use App\Models\CategoriaLote;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('el usuario ha iniciado sesión', function () {
    $proyecto = Proyecto::factory()->create();

    $response = $this->postJson("/api/proyectos/$proyecto->id/planos");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/planos");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "login" => $login, 
            "proyecto" => $proyecto
        ];
    },
    "Proyecto no vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar planos");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/planos");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar planos");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => Proyecto::factory()->create()
        ];
    },
    "Acceso indirecto" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Registrar planos");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => Proyecto::factory()->create()
        ];
    },
    "Proyecto vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar planos");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);
#endregion

#region Pruebas de validación
test('campos requeridos', function () {
    $proyecto = Proyecto::factory()->create();
    $proyectoId = $proyecto->id;
    $response = $this->actingAs(User::find(1))->postJson("api/proyectos/$proyectoId/planos", []);
    $response->assertJsonValidationErrors([
        "titulo" => "El campo 'titulo' es requerido."
    ]);
});
#endregion

test('solo puede haber un plano vigente', function () {
    $proyecto = Proyecto::factory()->create();
    $plano = Plano::factory()->for($proyecto)->create();
    $proyectoId = $proyecto->id;
    $response = $this->actingAs(User::find(1))->postJson("api/proyectos/$proyectoId/planos", [
        "titulo" => "Actualización 2"
    ]);
    $response->assertCreated();
    expect($plano->fresh()->is_vigente)->toBeFalse();
    expect($plano->fresh()->is_locked)->toBeTrue();
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
