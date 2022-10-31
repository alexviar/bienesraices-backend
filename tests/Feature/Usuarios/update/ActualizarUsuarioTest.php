<?php

use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;


test('el usuario ha iniciado sesión', function () {
    $response = $this->putJson('/api/usuarios/100');
    $response->assertUnauthorized();
});

it('verifica que el proyecto exista', function () {
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");
    $response = $this->actingAs($login)->putJson("/api/usuarios/100", []);

    $response->assertNotFound();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $usuario = $dataset["usuario"];
    $login = $dataset["login"];

    $response = $this->actingAs($login)->putJson("/api/usuarios/$usuario->id");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function () {
        $usuario = User::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol);
        return [
            "usuario" => $usuario,
            "login" => $login
        ];
    }
]);

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $usuario = $dataset["usuario"];
    $login = $dataset["login"];

    $response = $this->actingAs($login)->putJson("/api/usuarios/$usuario->id");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function () {
        $usuario = User::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Editar usuarios");
        $login->assignRole($rol);
        return [
            "usuario" => $usuario,
            "login" => $login
        ];
    },
    "Acceso indirecto" => function () {
        $usuario = User::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Editar usuarios");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "usuario" => $usuario,
            "login" => $login
        ];
    }
]);
#endregion

#region Pruebas de validación
it('los campos solo se validan cuando estan presentes', function () {
    /** @var TestCase $this */
    $usuario = User::factory()->create();
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");

    $response = $this->actingAs($login)->putJson("/api/usuarios/$usuario->id");

    $response->assertOk();
});
#endregion

it('actualiza el proyecto parcialmente', function ($dataset) {
    /** @var TestCase $this */
    /** @var User $usuario */
    $usuario = $dataset["usuario"];
    $data = $dataset["data"];
    $expectation = $dataset["expectations"];
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");

    $response = $this->actingAs($login)->putJson("/api/usuarios/$usuario->id", $data);

    $response->assertOk();
    $usuario->refresh();
    expect(Arr::only($usuario->getAttributes(), [
        "username",
        "email",
    ]))->toEqual(Arr::only($expectation, [
        "username",
        "email",
    ]));
    expect(Hash::check($expectation["password"], $usuario->password))->toBeTrue();
    expect($usuario->roles->pluck("name"))->toEqual($expectation["roles"]);
    expect($usuario->vendedor_id)->toBe($expectation["vendedor_id"]);
    expect($usuario->proyectos->pluck("id"))->toEqual($expectation["proyecto_ids"]);
})->with([
    function () {
        $usuario = User::factory()->create();
        $usuario->assignRole(Role::factory()->create());
        $usuario->vendedor()->associate(Vendedor::factory()->create());
        $usuario->proyectos()->attach(Proyecto::factory()->create());
        $usuario->save();

        Role::factory(2)->sequence([
            "name" => "Test role 1"
        ], [
            "name" => "Test role 2"
        ])->create();
        $vendedorId = Vendedor::factory()->create()->id;
        $proyectoIds = Proyecto::factory(3)->create()->pluck("id");
        $data = [
            "username" => "megustanlasoreos",
            "email" => "fake@example.com",
            "password" => 'paS$w0rd',
            "vendedor_id" => $vendedorId,
            "proyecto_ids" => $proyectoIds,
            "roles" => collect([
                "Test role 1",
                "Test role 2",
            ])
        ];
        return [
            "usuario" => $usuario,
            "data" => $data,
            "expectations" => $data
        ];
    },
    function () {
        $usuario = User::factory([
            "password" => "1234"
        ])->create();
        $usuario->assignRole(Role::factory()->create());
        $usuario->vendedor()->associate(Vendedor::factory()->create());
        $usuario->proyectos()->attach(Proyecto::factory()->create());
        $usuario->save();

        $data = [
            "username" => "megustanlasoreos",
        ];
        return [
            "usuario" => $usuario,
            "data" => $data,
            "expectations" => $data + [
                "email" => $usuario->email,
                "password" => '1234',
                "vendedor_id" => $usuario->vendedor_id,
                "proyecto_ids" => $usuario->proyectos->pluck("id"),
                "roles" => $usuario->roles->pluck("name")
            ]
        ];
    }
]);
