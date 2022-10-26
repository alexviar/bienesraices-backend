<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

test('El usuario ha iniciado sesión', function () {
    /** @var TestCase $this */
    
    $response = $this->putJson("/api/usuarios/100/activar");
    $response->assertUnauthorized();
    
    $response = $this->putJson("/api/usuarios/100/desactivar");
    $response->assertUnauthorized();
});

test('El usuario no existe', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");
    
    $response = $this->actingAs($login)->putJson("/api/usuarios/100/activar");
    $response->assertNotFound();

    
    $response = $this->actingAs($login)->putJson("/api/usuarios/100/desactivar");
    $response->assertNotFound();
});

it('Actualiza el estado del usuario', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $user = $dataset["user"];
    $user->forceFill(["estado" => 2])->update();
    
    $response = $this->actingAs($login)->putJson("/api/usuarios/$user->id/activar");
    $response->assertOk();
    $user->refresh();
    expect($user->estado)->toBe(1);
    
    $response = $this->actingAs($login)->putJson("/api/usuarios/$user->id/desactivar");
    $response->assertOk();
    $user->refresh();
    expect($user->estado)->toBe(2);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo([
            "Activar/Desactivar usuarios"
        ]);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "user" => User::factory()->create(),
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
        $permission->givePermissionTo([
            "Activar/Desactivar usuarios"
        ]);
        $rol->givePermissionTo([
            $permission
        ]);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "user" => User::factory()->create(),
        ];
    }
]);
