<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

test('El usuario ha iniciado sesión', function () {
    /** @var TestCase $this */

    $response = $this->postJson("/api/usuarios", []);
    $response->assertUnauthorized();
});

#region Pruebas de autorixación
test('Usuarios sin verficar no tienen autorización', function () {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory([
        "estado" => 1,
        "email_verified_at" => null,
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);

    $response = $this->actingAs($login)->postJson("/api/usuarios", []);
    $response->assertForbidden();
});
test('Usuarios bloqueados no tienen autorizacion', function () {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory([
        "estado" => 0
    ])->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);

    $response = $this->actingAs($login)->postJson("/api/usuarios", []);
    $response->assertForbidden();
});

test('Usuarios sin permisos no tienen autorizacion', function () {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory([
        "estado" => 1
    ])->create();

    $response = $this->actingAs($login)->postJson("/api/usuarios", []);
    $response->assertForbidden();
});
#endregion

#region Pruebas de validacion
test('Datos requeridos', function () {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory()->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        'roles' => []
    ]);
    $response->assertJsonValidationErrors([
        "username" => "El campo 'nombre de usuario' es requerido.",
        "email" => "El campo 'correo electrónico' es requerido.",
        "password" => "El campo 'contraseña' es requerido.",
        "roles" => "El campo 'roles' es requerido.",
    ]);
});

test('Contraseña poco segura', function($password, $expectation){
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory()->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        "password" => $password
    ]);
    $response->assertJsonValidationErrors([
        "password" => $expectation
    ]);
})->with([
    "0000" => [
        "0000",
        [
            "El campo 'contraseña' debe contener al menos 8 caracteres.",
            "El campo 'contraseña' debe contener al menos una letra.",
            "El campo 'contraseña' debe contener al menos un caracter especial (E.g. %&/()=?)."
        ]
    ],
    "asdf" => [
        "asdf",
        [
            "El campo 'contraseña' debe contener al menos 8 caracteres.",
            "El campo 'contraseña' debe contener al menos una mayuscula y una minuscrula.",
            "El campo 'contraseña' debe contener al menos un número.",
            "El campo 'contraseña' debe contener al menos un caracter especial (E.g. %&/()=?)."
        ]
    ],
    "%&%/" => [
        "%&%/",
        [
            "El campo 'contraseña' debe contener al menos 8 caracteres.",
            "El campo 'contraseña' debe contener al menos una mayuscula y una minuscrula.",
            "El campo 'contraseña' debe contener al menos un número.",
            "El campo 'contraseña' debe contener al menos una letra."
        ]
    ],
    "aS1$" => [
        "aS1$",
        [
            "El campo 'contraseña' debe contener al menos 8 caracteres.",
        ]
    ],
    "asdf1234%&/(" => [
        "asdf1234%&/(",
        [
            "El campo 'contraseña' debe contener al menos una mayuscula y una minuscrula.",
        ]
    ],
    "ASDF,1234" => [
        "ASDF,1234",
        [
            "El campo 'contraseña' debe contener al menos una mayuscula y una minuscrula.",
        ]
    ],
]);

test('Roles invalidos', function () {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory()->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        "roles" => [
            "Rol inexistente"
        ]
    ]);
    $response->assertJsonValidationErrors([
        "roles.0" => 'El rol seleccionado es inválido.'
    ]);
});

test('Email invalido', function() {
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory()->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        "email" => "alsñkfjñlasfnasflñanfñl"
    ]);
    $response->assertJsonValidationErrors([
        "email" => 'El correo electrónico es inválido.'
    ]); 
});

test('El nombre de usuario debe ser unico', function(){
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory()->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);
    User::factory([
        "email" => "fake@mail.com"
    ])->create();

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        "email" => "fake@mail.com"
    ]);
    $response->assertJsonValidationErrors([
        "email" => 'El correo electrónico ya está en uso.'
    ]);
});

test('El email debe ser unico', function(){
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory()->create();
    /** @var Role $rol */
    $rol = Role::factory()->create();
    $rol->givePermissionTo([
        "Registrar usuarios"
    ]);
    $login->assignRole($rol);
    User::factory([
        "username" => "d2+k2"
    ])->create();

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        "username" => "d2+k2"
    ]);
    $response->assertJsonValidationErrors([
        "username" => 'El nombre de usuario ya está en uso.'
    ]);
});
#endregion

it('registra un usuario', function(){
    /** @var TestCase $this */

    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");

    Role::factory(2)->sequence([
        "name" => "Test role 1"
    ], [
        "name" => "Test role 2"
    ])->create();

    $this->mock(SendEmailVerificationNotification::class, function(\Mockery\MockInterface $mock){
        $mock->shouldReceive("handle");
    });

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        "username" => "megustanlasoreos",
        "email" => "fake@example.com",
        "password" => 'paS$w0rd',
        "roles" => [
            "Test role 1",
            "Test role 2",
        ]
    ]);
    $response->assertCreated();
    
    /** @var User $user */
    $user = User::find($response->json("id"));
    expect($user->username)->toEqual("megustanlasoreos");
    expect(Hash::check('paS$w0rd', $user->password))->toBeTrue();
    expect($user->hasAllRoles([
        "Test role 1",
        "Test role 2",
    ]))->toBeTrue();
});

it('registra un usuario mediante diferentes formas de autorización', function($dataset) {
    /** @var TestCase $this */

    $login = $dataset["login"];

    Role::factory([
        "name" => "Test role"
    ])->create();

    $response = $this->actingAs($login)->postJson("/api/usuarios", [
        "username" => "oreolover",
        "email" => "fake@example.com",
        "password" => 'paS$w0rd',
        "roles" => [
            "Test role"
        ]
    ]);
    $response->assertCreated();
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory()->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo([
            "Registrar usuarios"
        ]);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    },
    "Acceso indirecto" => function(){
        /** @var User $login */
        $login = User::factory()->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $permission = Permission::factory()->create();
        $permission->givePermissionTo([
            "Registrar usuarios"
        ]);
        $rol->givePermissionTo([
            $permission
        ]);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    },
    // "Super usuario" => function(){
    //     /** @var User $login */
    //     $login = User::factory()->create();
    //     $login->assignRole("Super usuarios");
    //     return [
    //         "login" => $login
    //     ];
    // }
]);