<?php

use App\Models\Account;
use App\Models\Credito;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Permission;
use App\Models\Reserva;
use App\Models\Role;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;

test("el usuario ha iniciado sesión", function () {
    $transaccion =Transaccion::factory()->create();
    $response = $this->postJson("/api/transacciones/$transaccion->id/anular");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test("usuarios no autorizados", function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $transaccion = $dataset["transaccion"];

    $response = $this->actingAs($login)->postJson("/api/transacciones/$transaccion->id/anular");
    $response->assertForbidden();
})->with([
    "Sin permisos" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $login->assignRole($rol); 
        return [
            "login" => $login,
            "transaccion" => Transaccion::factory()->create()
        ];
    },
    "Ya anulado" => function(){
        $login = User::factory()->create();
        $login->assignRole("Super usuarios");
        return [
            "login" => $login,
            "transaccion" => Transaccion::factory([
                "estado" => 2
            ])->create()
        ];
    }
]);

test("usuarios autorizados", function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $transaccion =Transaccion::factory()->create();

    $response = $this->actingAs($login)->postJson("/api/transacciones/$transaccion->id/anular");
    expect($response->getStatusCode())->not->toBe(403);
})->with([
    "Acceso directo" => function(){
        /** @var User $login */
        $login = User::factory([
            "estado" => 1
        ])->create();
        /** @var Role $rol */
        $rol = Role::factory()->create();
        $rol->givePermissionTo("Anular transacciones");
        $login->assignRole($rol);
        return [
            "login" => $login,
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
        $permission->givePermissionTo("Anular transacciones");
        $rol->givePermissionTo($permission);
        $login->assignRole($rol);
        return [
            "login" => $login
        ];
    }
]);
#endregion

it("anula una transacción", function(){
    /** @var TestCase $this */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });
    /** @var User $login */
    $login = User::factory()->create();
    $login->assignRole("Super usuarios");
    $reserva = Reserva::factory([
        "moneda" => "USD",
        "importe" => "100",
        "saldo" => "50",
        "saldo_contado" => "10430.96",
        "saldo_credito" => "400",
        "estado" => 1
    ])->create();
    $venta = Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "400",
        "saldo" => "100",
        "estado" => 1
    ])->for($reserva)->credito("10030.96")->create();
    $credito = Credito::factory([
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 1,
        "estado" => 1
    ])->for($venta, "creditable")->create();
    $credito->build();
    $credito->cuotas[0]->update([
        "saldo" => "155.2700",
        "total_pagos" => "100",
    ]);
    $credito->cuotas[0]->pagos()->create([
        "fecha" => "2022-04-02",
        "moneda" => $credito->getCurrency()->code,
        "importe" => "100"
    ]);
    $transaccion = Transaccion::factory([
        "fecha" => "2022-04-02",
    ])->create();
    $transaccion->detalles()->create([
        "pagable_id" => $reserva->id,
        "pagable_type" => $reserva->getMorphClass(),
        "referencia" => $reserva->getReferencia(),
        "importe" => "50",
        "moneda" => "USD"
    ]);
    $transaccion->detalles()->create([
        "pagable_id" => $venta->id,
        "pagable_type" => $venta->getMorphClass(),
        "referencia" => $venta->getReferencia(),
        "importe" => "300",
        "moneda" => "USD"
    ]);
    $transaccion->detalles()->create([
        "pagable_id" => $credito->cuotas[0]->id,
        "pagable_type" => $credito->cuotas[0]->getMorphClass(),
        "referencia" => $credito->cuotas[0]->getReferencia(),
        "importe" => "100",
        "moneda" => "USD"
    ]);
    $account = Account::create([
        "cliente_id" => $venta->cliente_id,
        "moneda" => $venta->moneda,
        "balance" => "450"
    ]);
    $transaccion->detalles()->create([
        "pagable_id" => $account->id,
        "pagable_type" => $account->getMorphClass(),
        "referencia" => "Excedentes",
        "importe" => "50",
        "moneda" => "USD"
    ]);
    $this->travelTo(Carbon::create(2022, 4, 2));

    $response = $this->actingAs($login)->postJson("/api/transacciones/$transaccion->id/anular", [
        "motivo" => "Datos erroneos"
    ]);
    
    $response->assertCreated();
    $transaccion->refresh();
    $anulacion = $transaccion->anulacion;
    $this->assertTrue($anulacion->anulable->is($transaccion));
    expect($anulacion->fecha->isSameAs(Carbon::today()))->toBeTrue();
    expect($anulacion->motivo)->toBe("Datos erroneos");
    expect($transaccion->estado)->toEqual(2);
    expect((string)$venta->refresh()->saldo->round())->toBe("400.00 USD");
    expect((string)$reserva->refresh()->saldo->round())->toBe("100.00 USD");
    expect((string)$credito->cuotas[0]->refresh()->saldo->round())->toBe("255.26 USD");
    expect((string)$credito->cuotas[0]->total_pagos->round())->toBe("0.00 USD");
    expect((string)$account->refresh()->balance->round())->toBe("400.00 USD");
    expect($credito->cuotas[0]->pagos)->toHaveCount(0);
});