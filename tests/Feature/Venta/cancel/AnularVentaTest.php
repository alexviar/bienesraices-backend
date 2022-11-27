<?php

use App\Models\Anulacion;
use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $venta = Venta::factory()->create();

    $response = $this->postJson("/api/proyectos/$venta->proyecto_id/ventas/$venta->id/anular");
    $response->assertUnauthorized();
});

test('not found', function($venta){
    $login = User::factory()->create();
    
    $response = $this->actingAs($login)->postJson("/api/proyectos/$venta->proyecto_id/ventas/$venta->id/anular");
    $response->assertNotFound();
})->with([
    fn () => (new Venta())->forceFill(["proyecto_id"=>100, "id" => 100]),
    fn () => (new Venta())->forceFill(["proyecto_id"=>Proyecto::factory()->create()->id, "id" => 100])
]);

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $venta = $dataset["venta"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$venta->proyecto_id/ventas/$venta->id/anular");
    $response->assertForbidden();
})->with([
    "Sin permiso" => function(){
        $venta = Venta::factory()->create();
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
        $venta = Venta::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Anular ventas");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "venta" => $venta
        ];
    },
    "Vendedor no vinculado" => function(){
        $venta = Venta::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Anular ventas");
        $login->assignRole($rol);
        $login->vendedor()->associate(Vendedor::factory()->create());
        return [
            "login" => $login,
            "venta" => $venta
        ];
    },
    "Ya anulado" => function(){
        $venta = Venta::factory([
            "estado" => 2
        ])->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "venta" => $venta
        ];
    },
    "Pagado" => function(){
        $venta = Venta::factory([
            "saldo" => 1
        ])->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        $login->assignRole("Super usuarios");
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

    $response = $this->actingAs($login)->postJson("/api/proyectos/{$venta->proyecto->id}/ventas/$venta->id/anular");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Anular ventas");
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
        $permission->givePermissionTo("Anular ventas");
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
        $rol->givePermissionTo("Anular ventas");
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
        $rol->givePermissionTo("Anular ventas");
        $login->assignRole($rol);
        $login->vendedor()->associate($venta->vendedor);
        return [
            "login" => $login,
            "venta" => $venta
        ];
    },
]);
#endregion

it('anula la venta', function() {
    /** @var TestCase $this */
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");
    $venta = Venta::factory([
        "estado" => 1
    ])->create();
    expect($venta->lote->estado)->toBe(4);

    $response = $this->actingAs($login)->postJson("/api/proyectos/$venta->proyecto_id/ventas/$venta->id/anular", [
        "motivo" => "Datos erroneos"
    ]);
    $response->assertCreated();
    $venta->refresh();
    $anulacion = $venta->anulacion;
    $this->assertTrue($anulacion->anulable->is($venta));
    expect($anulacion->fecha->isSameAs(Carbon::today()))->toBeTrue();
    expect($anulacion->motivo)->toBe("Datos erroneos");
    expect($venta->estado)->toBe(2);
    expect($venta->lote->fresh()->estado)->toBe(1);
});