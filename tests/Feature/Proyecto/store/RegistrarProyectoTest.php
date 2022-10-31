<?php

use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;


test('el usuario ha iniciado sesión', function () {
    $response = $this->postJson('/api/proyectos');
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $login->assignRole($rol);
    $response = $this->actingAs($login)->postJson('/api/proyectos');
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $response = $this->actingAs($login)->postJson('/api/proyectos');
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar proyectos");
        $login->assignRole($rol);
        return [
            "login" => $login
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
        $permission->givePermissionTo("Registrar proyectos");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

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
