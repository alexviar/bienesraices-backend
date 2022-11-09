<?php

use App\Events\ReservaCreated;
use App\Models\Lote;
use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendedor;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Event;

test('el usuario ha iniciado sesión', function () {
    $proyecto = Proyecto::factory()->create();

    $response = $this->postJson("/api/proyectos/$proyecto->id/reservas");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/reservas");
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
        $rol->givePermissionTo("Registrar reservas");
        $login->assignRole($rol);
        $login->proyectos()->attach(Proyecto::factory()->create());
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Vendedor no vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar reservas");
        $login->assignRole($rol);
        $login->vendedor()->associate(Vendedor::factory()->create());
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

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/reservas", [
        "vendedor_id" => $login->vendedor_id
    ]);
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar reservas");
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => Proyecto::factory()->create()
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
        $permission->givePermissionTo("Registrar reservas");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login,
            "proyecto" => Proyecto::factory()->create()
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
        $rol->givePermissionTo("Registrar reservas");
        $login->assignRole($rol);
        $login->proyectos()->attach($proyecto);
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
    "Vendedor vinculado" => function(){
        $proyecto = Proyecto::factory()->create();
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Registrar reservas");
        $login->assignRole($rol);
        $login->vendedor()->associate(Vendedor::factory()->create());
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
]);
#endregion

it('registra una nueva reserva', function ($dataset) {

    $data = $dataset["data"];
    $proyectoId = $data["proyecto_id"];
    
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/reservas", $data);
    
    $response->assertCreated();
    $reserva = Reserva::find($response->json("id"));
    expect($reserva->getAttributes())->toMatchArray([
        "importe" => (string) BigDecimal::of($data["importe"])->toScale(4, RoundingMode::HALF_UP),
        "saldo" => (string) BigDecimal::of($data["importe"])->toScale(4, RoundingMode::HALF_UP),
        "saldo_contado" => (string) BigDecimal::of($data["saldo_contado"])->toScale(4, RoundingMode::HALF_UP),
        "saldo_credito" => (string) BigDecimal::of($data["saldo_credito"])->toScale(4, RoundingMode::HALF_UP),
    ] + $data);
})->with([
    function(){
        $data = Reserva::factory()->for(Lote::factory()->disponible())->raw();
        return [
            "data" => $data,
        ];
    }
]);

it('despacha el evento de nueva reserva creada', function ($dataset) {

    $data = $dataset["data"];
    $proyectoId = $data["proyecto_id"];

    Event::fake();
    
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/reservas", $data);
    
    $response->assertCreated();
    Event::assertDispatched(ReservaCreated::class, function(ReservaCreated $event) use($response){
        $this->assertSame($response->json("id"), $event->reserva->id);
        return true;
    });
    
})->with([
    function(){
        $data = Reserva::factory()->for(Lote::factory()->disponible())->raw();
        return [
            "data" => $data,
        ];
    }
]);
