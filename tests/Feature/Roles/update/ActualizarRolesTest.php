<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $response = $this->putJson('/api/roles/100');
    $response->assertUnauthorized();
});

it('verifica que el rol existe', function () {
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $response = $this->actingAs($login)->putJson("/api/roles/100");
    $response->assertNotFound();
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
    $login->assignRole(Role::factory()->create());
    $response = $this->actingAs($login)->putJson("/api/roles/$rol->id");
    $response->assertForbidden();
});

test('usuarios autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $rol = Role::factory()->create();
    $response = $this->actingAs($login)->putJson("/api/roles/$rol->id");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function () {
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Editar roles");
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    },
    "Acceso indirecto" => function () {
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo("Editar roles");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

#region Pruebas de validación
it('los campos solo se validan cuando estan presentes', function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");
    $rol = Role::factory()->create();

    $response = $this->actingAs($login)->putJson("/api/roles/$rol->id", []);

    expect($response->getStatusCode())->not->toBe(422);
});

test("datos requeridos", function (){
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");
    $rol = Role::factory()->create();

    $response = $this->actingAs($login)->putJson("/api/roles/$rol->id", [
        "name" => "",
        "description" => "",
        "permissions" => []
    ]);
    $response->assertJsonValidationErrors([
        "name"=> "El campo 'nombre' es requerido.",
        "permissions" => "Debe asignar al menos un permiso."
    ]);
    $response->assertJsonMissingValidationErrors([
        "description",
    ]);
});

test("permisos inexistentes", function () {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();
    $login->assignRole("Super usuarios");
    $rol = Role::factory()->create();

    $response = $this->actingAs($login)->putJson("/api/roles/$rol->id", [
        "permissions" => ["Permiso inexistente"]
    ]);
    $response->assertJsonValidationErrors([
        "permissions.0" => "El permiso seleccionado es inválido."
    ]);
});
#endregion

it('actualiza el rol parcialmente', function ($dataset) {
    /** @var TestCase $this */
    $rol = $dataset["rol"];
    $login = $dataset["login"];
    $data = $dataset["data"];
    $expectations = $dataset["expectations"];

    $response = $this->actingAs($login)->putJson("/api/roles/$rol->id", $data);

    $response->assertOk();
    $rol->refresh();
    expect(Arr::only($rol->getAttributes(), [
        "name",
        "description",
    ]))->toEqual(Arr::only($expectations, [
        "name",
        "description",
    ]));
    expect($rol->getPermissionNames()->toArray())->toBe($expectations["permissions"]);
})->with([
    function () {
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver usuarios", "Ver roles");
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        $login->assignRole("Super usuarios");
    
        Permission::factory(2)->sequence([
            "name" => "Permiso de prueba 1"
        ],[
            "name" => "Permiso de prueba 2"
        ])->create();

        $data = [
            "name" => "Rol de prueba",
            "description" => "Este es un rol de prueba",
            "permissions" => ["Permiso de prueba 1", "Permiso de prueba 2"]
        ];
        return [
            "rol" => $rol,
            "login" => $login,
            "data" => $data,
            "expectations" => $data
        ];
    },
    function () {
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Ver usuarios", "Ver roles");
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        $login->assignRole("Super usuarios");

        $data = [
            "name" => "Rol de prueba",
        ];
        return [
            "rol" => $rol,
            "login" => $login,
            "data" => $data,
            "expectations" => $data + [
                "description" => $rol->description,
                "permissions" => ["Ver usuarios", "Ver roles"]
            ]
        ];
    }
]);