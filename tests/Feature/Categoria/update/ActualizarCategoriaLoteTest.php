<?php

use App\Models\CategoriaLote;
use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->putJson("/api/proyectos/100/categorias/100");
    $response->assertUnauthorized();
});

test('not found', function($dataset){
    /** @var TestCase $this */
    $login = $dataset["login"];
    $categoria = $dataset["categoria"];

    $response = $this->actingAs($login)->putJson("/api/proyectos/$categoria->proyecto_id/categorias/$categoria->id");
    $response->assertNotFound();
})->with([
    "Proyecto inexistente" => function(){
        $proyecto = new Proyecto();
        $proyecto->id = 100;
        $categoria = new CategoriaLote(["proyecto_id" => $proyecto->id]);
        $categoria->id = 100;
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "categoria" => $categoria
        ];
    },
    "Categoría inexistente" => function(){
        $proyecto = Proyecto::factory()->create();
        $categoria = new CategoriaLote(["proyecto_id" => $proyecto->id]);
        $categoria->id = 100;
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "categoria" => $categoria
        ];
    }
]);

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $categoria = $dataset["categoria"];

    $response = $this->actingAs($login)->putJson("/api/proyectos/$categoria->proyecto_id/categorias/$categoria->id");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $proyecto = Proyecto::factory()->create();
        $categoria = CategoriaLote::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "login" => $login, 
            "categoria" => $categoria
        ];
    },
    "Proyecto no vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        $categoria = CategoriaLote::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Editar categorías");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "categoria" => $categoria
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $categoria = $dataset["categoria"];

    $response = $this->actingAs($login)->putJson("/api/proyectos/$categoria->proyecto_id/categorias/$categoria->id");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        $proyecto = Proyecto::factory()->create();
        $categoria = CategoriaLote::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Editar categorías");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "categoria" => $categoria
        ];
    },
    "Acceso indirecto" => function(){
        $proyecto = Proyecto::factory()->create();
        $categoria = CategoriaLote::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Editar categorías");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "categoria" => $categoria
        ];
    },
    "Proyecto vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        $categoria = CategoriaLote::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Editar categorías");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "categoria" => $categoria
        ];
    }
]);
#endregion

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