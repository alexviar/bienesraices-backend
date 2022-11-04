<?php

use App\Models\CategoriaLote;
use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson("/api/proyectos/100/categorias");
    $response->assertUnauthorized();
});

test('not found', function($dataset){
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/categorias");
    $response->assertNotFound();
})->with([
    "Proyecto inexistente" => function(){
        $proyecto = new Proyecto();
        $proyecto->id = 100;
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/categorias");
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
        $rol->givePermissionTo("Registrar categorías");
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

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/categorias");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar categorías");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Acceso indirecto" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Registrar categorías");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => $proyecto
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
        $rol->givePermissionTo("Registrar categorías");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);
#endregion

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
