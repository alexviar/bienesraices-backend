<?php

use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;

test('el usuario ha iniciado sesión', function () {
    $venta = Venta::factory()->create();

    $response = $this->getJson("/proyectos/$venta->proyecto_id/ventas/$venta->id/nota-venta");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $venta = $dataset["venta"];

    $response = $this->actingAs($login)->getJson("/proyectos/$venta->proyecto_id/ventas/$venta->id/nota-venta");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $venta = Venta::factory([
            "tipo" => 1
        ])->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "login" => $login, 
            "venta" => $venta
        ];
    },
    "Proyecto no vinculado" => function(){
        $venta = Venta::factory([
            "tipo" => 1
        ])->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir comprobantes de venta");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "venta" => $venta
        ];
    },
    "Vendedor no vinculado" => function(){
        $venta = Venta::factory([
            "tipo" => 1
        ])->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir comprobantes de venta");
        $login->assignRole($rol);
        $login->vendedor()->associate(Vendedor::factory()->create());
        return [
            "login" => $login,
            "venta" => $venta
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $venta = $dataset["venta"];

    $response = $this->actingAs($login)->getJson("/proyectos/$venta->proyecto_id/ventas/$venta->id/nota-venta");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir comprobantes de venta");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "venta" => Venta::factory()->create()
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
        $permission->givePermissionTo("Imprimir comprobantes de venta");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "venta" => Venta::factory()->create()
        ];
    },
    "Proyecto vinculado" => function(){
        $venta = Venta::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir comprobantes de venta");
        $login->assignRole($rol);
        $login->proyectos()->attach($venta->proyecto);
        return [
            "login" => $login,
            "venta" => $venta
        ];
    },
    "Vendedor vinculado" => function(){
        $venta = Venta::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Imprimir comprobantes de venta");
        $login->assignRole($rol);
        $login->vendedor()->associate($venta->vendedor);
        return [
            "login" => $login,
            "venta" => $venta
        ];
    },
]);
#endregion
