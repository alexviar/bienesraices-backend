<?php

use App\Models\Manzana;
use App\Models\Permission;
use App\Models\Plano;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson("/api/proyectos/100/manzanas");
    $response->assertUnauthorized();
});

test('not found', function($dataset){
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/manzanas");
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
    },
    "Plano obsoleto" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "proyecto" => Plano::factory([
                "estado" => 0 //Obsoleto y Editable
            ])->create()->proyecto
        ];
    },
]);

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/manzanas");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
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
    "Plano vigente no editable" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar manzanas");
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "proyecto" => Plano::factory([
                "estado" => 3 //Vigente y No editable
            ])->create()->proyecto
        ];
    },
    "Proyecto no vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar manzanas");
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

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/manzanas");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar manzanas");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Acceso indirecto" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Registrar manzanas");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Proyecto vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        Plano::factory()->for($proyecto)->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar manzanas");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    }
]);
#endregion

#region Pruebas de validacion
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
#endregion

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