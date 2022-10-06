<?php

use App\Events\VentaCreated;
use App\Models\Credito;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Talonario;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

function read_csv($filename){
    $file = fopen($filename, "r");

    while (($data = fgetcsv($file, 0, "\t")) !== FALSE) {
        yield $data;
    }

    fclose($file);

}

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
        ])->contado()->withoutReserva()->raw();
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
        ])->credito("10030.96")->withoutReserva()->raw();
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
                "credito" => Arr::except($dataCredito, ["cuota_inicial"])
            ]
        ];
    },
    function(){
        //Venta al contado
        $reserva = Reserva::factory([
            "moneda" => "USD",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
            "moneda" => "USD",
            "importe" => "10530.96",
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
                    "importe" => "10430.9600",
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
            "moneda" => "USD",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
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
                    "importe" => "72599.4800",
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
            "moneda" => "BOB",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
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
                    "importe" => "1520.5500",
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
            "moneda" => "USD",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
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
                    "importe_pendiente" => "69815.4800",
                    "importe" => "2784.0000",
                    "reserva_id" => $reserva->id,
                    "cliente_id" => $reserva->cliente_id,
                    "vendedor_id" => $reserva->vendedor_id,
                    "lote_id" => $reserva->lote_id
                ],
                "credito" => Arr::except($dataCredito, ["cuota_inicial"])
            ]
        ];
    },
    function(){
        Talonario::create([
            "tipo" => Credito::class,
            "siguiente" => 1
        ]);
        $reserva = Reserva::factory([
            "moneda" => "BOB",
            "importe" => "100",
            "saldo_contado" => "10430.96",
            "saldo_credito" => "400",
        ])->create();
        $data = Venta::factory([
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
                    // "importe" => "1520.5500",
                    "importe_pendiente" => "1462.2400",
                    "importe" => "58.3100",
                    "reserva_id" => $reserva->id,
                    "cliente_id" => $reserva->cliente_id,
                    "vendedor_id" => $reserva->vendedor_id,
                    "lote_id" => $reserva->lote_id
                ],
                "credito" => Arr::except($dataCredito, ["cuota_inicial"])
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
    ])->credito("10030.96")->withReserva(false)->raw();
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

    $lote = Lote::factory()->create();
    $now = Carbon::now();
    $reserva = Reserva::factory([
        "fecha" => $now->format("Y-m-d"),
        "saldo_contado" => "0",
        "saldo_credito" => "0",
    ])->for($lote)->create();

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
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/ventas", $data);

    $response->assertCreated();

});

it('registra la transaccion', function () {
    /** @var TestCase $this  */

    $data = Venta::factory([
        "moneda" => "USD",
        "importe" => "10530.96",
    ])->contado()->withReserva(false)->raw();
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