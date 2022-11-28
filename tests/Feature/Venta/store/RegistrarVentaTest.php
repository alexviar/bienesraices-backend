<?php

use App\Events\VentaCreated;
use App\Models\Credito;
use App\Models\Lote;
use App\Models\Permission;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Role;
use App\Models\Talonario;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

test('el usuario ha iniciado sesión', function () {
    $proyecto = Proyecto::factory()->create();

    $response = $this->postJson("/api/proyectos/$proyecto->id/ventas");
    $response->assertUnauthorized();
});

#region Pruebas de autorización
test('usuarios sin permiso no estan autorizados', function ($dataset) {
    /** @var TestCase $this */
    $login = $dataset["login"];
    $proyecto = $dataset["proyecto"];

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/ventas");
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
        $rol->givePermissionTo("Registrar ventas");
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
        $rol->givePermissionTo("Registrar ventas");
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

    $response = $this->actingAs($login)->postJson("/api/proyectos/$proyecto->id/ventas", [
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
        $rol->givePermissionTo("Registrar ventas");
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
        $permission->givePermissionTo("Registrar ventas");
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
        $rol->givePermissionTo("Registrar ventas");
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
        $rol->givePermissionTo("Registrar ventas");
        $login->assignRole($rol);
        $login->vendedor()->associate(Vendedor::factory()->create());
        return [
            "login" => $login,
            "proyecto" => $proyecto
        ];
    },
]);
#endregion

test('La fecha no puede estar en el futuro', function(){
    /** @var TestCase $this */

    $proyecto = Proyecto::factory()->create();


    $today = Carbon::today();
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyecto->id/ventas", [
        "fecha" => $today->clone()->addDay()->format("Y-m-d")
    ]);
    $response->assertJsonValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyecto->id/ventas", [
        "fecha" => $today->format("Y-m-d")
    ]);
    $response->assertJsonMissingValidationErrors([
        "fecha" => "El campo 'fecha' no puede ser posterior a la fecha actual."
    ]);
});

it('Registra una venta', function ($dataset) {
    /** @var TestCase $this */

    $data = $dataset["data"];
    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    $expectations = $dataset["expectations"];

    $this->assertEquals($expectations["venta"], Arr::only($venta->getAttributes(), array_keys($expectations["venta"])));
    if($venta->tipo == 2){
        $expectedCreditoData = [
            "creditable_id" => $venta->id,
            "creditable_type" => $venta->getMorphClass()
        ] + $expectations["credito"] ;
        $this->assertEquals($expectedCreditoData, Arr::only($venta->credito->getAttributes(), array_keys($expectedCreditoData)));
        
        if(isset($dataset["plan_pagos_path"])){
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk("tests");
            foreach(read_csv($disk->path($dataset["plan_pagos_path"])) as $row){
                $this->assertDatabaseHas("cuotas", ["vencimiento" => $row[1], "credito_id" => $venta->credito->id, "numero" => $row[0], "importe" => $row[3], "saldo" => $row[3], "saldo_capital" => $row[6]]);
            }
        }
    }
})->with([
    function(){
        //Venta al contado
        $data = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.96",
        ])->contado()->for(Lote::factory()->disponible())->withoutReserva()->raw();
        unset($data["importe_pendiente"]);
        return [
            "data" => $data,
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda",
                    "cliente_id",
                    "vendedor_id",
                    "lote_id"
                ]) + [
                    "importe" => "10530.9600"
                ]
            ]
        ];
    },
    function(){
        //Venta al credito
        Talonario::create([
            "tipo" => Credito::class,
            "siguiente" => 1
        ]);
        $data = Venta::factory([
            "fecha" => "2022-02-28",
            "moneda" => "USD",
            "importe" => "500",
        ])->credito("10030.96")->for(Lote::factory()->disponible())->withoutReserva()->raw();
        $dataCredito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1,
        ])->raw();
        $data["credito"] = $dataCredito;

        return [
            "data" => $data,
            "plan_pagos_path" => "Feature/Venta/csv/plan_pagos_1.csv",
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda",
                    "cliente_id",
                    "vendedor_id",
                    "proyecto_id",
                    "lote_id"
                ]) + [
                    "importe" => "500.0000",
                    "importe_pendiente" => "10030.9600"
                ],
                "credito" => Arr::except($dataCredito, ["importe_cuotas"])
            ]
        ];
    },
    function(){
        //Venta al credito
        Talonario::create([
            "tipo" => Credito::class,
            "siguiente" => 1
        ]);
        $data = Venta::factory([
            "fecha" => "2022-02-28",
            "moneda" => "USD",
            "importe" => "500",
        ])->credito("10030.96")->for(Lote::factory()->disponible())->withoutReserva()->raw();
        $dataCredito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1,
            "tasa_interes" => "0.1500"
        ])->raw();
        $data["credito"] = $dataCredito;

        return [
            "data" => $data,
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda",
                    "cliente_id",
                    "vendedor_id",
                    "proyecto_id",
                    "lote_id"
                ]) + [
                    "importe" => "500.0000",
                    "importe_pendiente" => "10030.9600"
                ],
                "credito" => Arr::except($dataCredito, ["importe_cuotas"])
            ]
        ];
    },
    function(){
        //Venta al contado
        $reserva = Reserva::factory([
            "fecha" => Carbon::now()->format("Y-m-d"),
            "moneda" => "USD",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.96",
        ])->contado()->for($reserva)->raw();
        unset($data["importe_pendiente"], $data["cliente_id"], $data["vendedor_id"], $data["lote_id"]);
        return [
            "data" => $data,
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda"
                ]) + [
                    "importe" => "10530.9600",
                    "reserva_id" => $reserva->id,
                    "cliente_id" => $reserva->cliente_id,
                    "vendedor_id" => $reserva->vendedor_id,
                    "lote_id" => $reserva->lote_id
                ]
            ]
        ];
    },
    function(){
        //Venta al contado
        $reserva = Reserva::factory([
            "fecha" => Carbon::now()->format("Y-m-d"),
            "moneda" => "USD",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
            "importe" => "72600.0000",
            "moneda" => "BOB",
        ])->contado()->for($reserva)->raw();
        unset($data["importe_pendiente"]);
        return [
            "data" => $data,
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda"
                ]) + [
                    "importe" => "72600.0000",
                    "reserva_id" => $reserva->id,
                    "cliente_id" => $reserva->cliente_id,
                    "vendedor_id" => $reserva->vendedor_id,
                    "lote_id" => $reserva->lote_id
                ]
            ]
        ];
    },
    function(){
        //Venta al contado
        $reserva = Reserva::factory([
            "fecha" => Carbon::now()->format("Y-m-d"),
            "moneda" => "BOB",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
            "importe" => "1500.1234",
            "moneda" => "USD",
        ])->contado()->for($reserva)->raw();
        unset($data["importe_pendiente"]);
        return [
            "data" => $data,
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda"
                ]) + [
                    "importe" => "1500.1200",
                    "reserva_id" => $reserva->id,
                    "cliente_id" => $reserva->cliente_id,
                    "vendedor_id" => $reserva->vendedor_id,
                    "lote_id" => $reserva->lote_id
                ]
            ]
        ];
    },
    function(){
        Talonario::create([
            "tipo" => Credito::class,
            "siguiente" => 1
        ]);
        $reserva = Reserva::factory([
            "fecha" => Carbon::now()->format("Y-m-d"),
            "moneda" => "USD",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
            "importe" => "3000",
            "moneda" => "BOB",
        ])->credito("100")->for($reserva)->raw();
        $dataCredito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1,
        ])->raw();
        $data["credito"] = $dataCredito;
        return [
            "data" => $data,
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda"
                ]) + [
                    "importe_pendiente" => "100.0000",
                    "importe" => "3000.0000",
                    "reserva_id" => $reserva->id,
                    "cliente_id" => $reserva->cliente_id,
                    "vendedor_id" => $reserva->vendedor_id,
                    "lote_id" => $reserva->lote_id
                ],
                "credito" => Arr::except($dataCredito, ["importe_cuotas"])
            ]
        ];
    },
    function(){
        Talonario::create([
            "tipo" => Credito::class,
            "siguiente" => 1
        ]);
        $reserva = Reserva::factory([
            "fecha" => Carbon::now()->format("Y-m-d"),
            "moneda" => "BOB",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
            "importe" => "1500",
            "moneda" => "USD",
        ])->credito("100")->for($reserva)->raw();
        $dataCredito = Credito::factory([
            "plazo" => 48,
            "periodo_pago" => 1,
            "dia_pago" => 1,
        ])->raw();
        $data["credito"] = $dataCredito;
        return [
            "data" => $data,
            "expectations" => [
                "venta" => Arr::only($data, [
                    "fecha",
                    "tipo",
                    "moneda"
                ]) + [
                    "importe" => "1500.0000",
                    "importe_pendiente" => "100.0000",
                    "reserva_id" => $reserva->id,
                    "cliente_id" => $reserva->cliente_id,
                    "vendedor_id" => $reserva->vendedor_id,
                    "lote_id" => $reserva->lote_id
                ],
                "credito" => Arr::except($dataCredito, ["importe_cuotas"])
            ]
        ];
    },
]);

test("Pagos programados el 31 de cada mes", function(){
    Talonario::create([
        "tipo" => Credito::class,
        "siguiente" => 1
    ]);
    $data = Venta::factory([
        "fecha" => "2022-02-28",
        "moneda" => "USD",
        "importe" => "500",
    ])->credito("10030.96")->for(Lote::factory())->withReserva(false)->raw();
    $dataCredito = Credito::factory([
        "plazo" => 48,
        "periodo_pago" => 1,
        "dia_pago" => 31,
    ])->raw();
    $data += [
        "credito" => $dataCredito
    ];

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();
    $id = $response->json("id");

    $venta = Venta::find($id);

    $this->assertSame(1, $venta->estado);
    $this->assertSame(2, $venta->tipo);
    $keys = [
        "fecha",
        "moneda",
        "proyecto_id",
        "lote_id",
        "cliente_id",
        "vendedor_id",
    ];
    $this->assertEquals(Arr::only($data, $keys), Arr::only($venta->getAttributes(), $keys));
    $this->assertEquals((string) BigDecimal::of($data["importe"])->toScale(4), (string) $venta->importe->amount);
    $this->assertEquals((string) BigDecimal::of($data["importe_pendiente"])->toScale(4), (string) $venta->importe_pendiente->amount);
    
    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk("tests");
    foreach(read_csv($disk->path("Feature/Venta/csv/plan_pagos_4.csv")) as $row){
        $this->assertDatabaseHas("cuotas", ["vencimiento" => $row[1], "credito_id" => $venta->credito->id, "numero" => $row[0], "importe" => $row[3], "saldo" => $row[3], "saldo_capital" => $row[6]]);
    }
});

test("Un lote que ha sido reservado por un cliente no puede ser vendido a otro, a menos que la reserva haya expirado", function (){
    $now = Carbon::now();
    $reserva = Reserva::factory([
        "fecha" => $now->format("Y-m-d"),
        "saldo_contado" => "0",
        "saldo_credito" => "0",
    ])->create();
    $lote = $reserva->lote;

    //Venta al contado
    $data = Venta::factory()->for($lote)->contado()->withReserva(false)->raw();
    unset($data["importe_pendiente"]);

    $proyectoId = $data["proyecto_id"];

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertJsonValidationErrors([
        "lote_id" => "El lote ha sido reservado por otro cliente."
    ]);

    $this->travelTo($reserva->vencimiento);

    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertJsonValidationErrors([
        "lote_id" => "El lote ha sido reservado por otro cliente."
    ]);

    $this->travel(1)->days();
    $lote->estado = 1;
    $lote->update();
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();

});

it('dispatch event', function () {
    /** @var TestCase $this  */

    $data = Venta::factory([
        "moneda" => "USD",
        "importe" => "10530.96",
    ])->for(Lote::factory())->contado()->withReserva(false)->raw();
    $proyectoId = $data["proyecto_id"];
    unset($data["importe_pendiente"]);

    Event::fake();
    
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);
    $response->assertCreated();
    $id = $response->json("id");
    $venta = Venta::find($id);
    Event::assertDispatched(VentaCreated::class, function(VentaCreated $event) use($venta){
        $this->assertEquals($event->userId, 1);
        $this->assertEquals($event->venta->id, $venta->id);
        return true;
    });
});