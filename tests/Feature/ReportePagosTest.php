<?php

use App\Models\Account;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Tests\TestCase;

use function Ozzie\Nest\describe;
use function Ozzie\Nest\test;

describe("Reporte de pagos", function (){
    function prepareData(){
        $proyecto1 = Proyecto::factory()->create();
        $proyecto2 = Proyecto::factory()->create();

        $transaccion = Transaccion::factory([
            "fecha" => "2019-12-31",
            "moneda" => "BOB",
            "importe" => "1770.7"
        ])->create();
        DetalleTransaccion::factory([
            "moneda" => "BOB",
            "importe" => "1770.7",
        ])->for($transaccion)
            ->for(Cuota::factory()
                ->for(Credito::factory()
                    ->for(Venta::factory()->credito("9000")->for($proyecto2), "creditable")
                ),
            "pagable")
        ->create();
        
        $transaccion = Transaccion::factory([
            "fecha" => "2020-01-01",
            "moneda" => "BOB",
            "importe" => "1000",
        ])->create();
        DetalleTransaccion::factory([
            "moneda" => "BOB",
            "importe" => "304",
        ])->for($transaccion)->for(Venta::factory()->contado()->for($proyecto1), "pagable")
        ->create();
        DetalleTransaccion::factory([
            "moneda" => "USD",
            "importe" => "100",
        ])->for($transaccion)->for(Venta::factory()->contado()->for($proyecto2), "pagable")
        ->create();
        
        $transaccion = Transaccion::factory([
            "fecha" => "2020-01-01",
            "moneda" => "USD",
            "importe" => "1000",
        ])->create();
        DetalleTransaccion::factory([
            "moneda" => "BOB",
            "importe" => "696",
        ])->for($transaccion)->for(Reserva::factory()->for($proyecto1), "pagable")
        ->create();
        DetalleTransaccion::factory([
            "moneda" => "USD",
            "importe" => "900",
        ])->for($transaccion)->for(Venta::factory()->credito("19100")->for($proyecto1), "pagable")
        ->create();

        $transaccion = Transaccion::factory([
            "fecha" => "2020-01-02",
            "moneda" => "BOB",
            "importe" => "1246.87",
            "estado" => 2
        ])->create();
        DetalleTransaccion::factory([
            "moneda" => "USD",
            "importe" => "179.14",
        ])->for($transaccion)->for(Reserva::factory()->for($proyecto2), "pagable")
        ->create();
        DetalleTransaccion::factory([
            "moneda" => "BOB",
            "importe" => "0.06",
            "pagable_id" => 234,//$this->faker->randomNumber(),
            "pagable_type" => Account::class,
        ])->for($transaccion)/*->for(Account::factory([
            "moneda" => "BOB"
        ])->for($transaccion->cliente), "pagable")*/
        ->create();

        $transaccion = Transaccion::factory([
            "fecha" => "2020-01-13",
            "moneda" => "USD",
            "importe" => "241.28",
            "estado" => 1
        ])->create();
        DetalleTransaccion::factory([
            "moneda" => "USD",
            "importe" => "241.28",
        ])->for($transaccion)
        ->for(Cuota::factory()
            ->for(Credito::factory()
                ->for(Venta::factory()->credito("9000")->for($proyecto2), "creditable")
            ), "pagable")
        ->create();

        $transaccion = Transaccion::factory([
            "fecha" => "2020-01-07",
            "moneda" => "BOB",
            "importe" => "246.87",
            "estado" => 1
        ])->create();
        DetalleTransaccion::factory([
            "moneda" => "USD",
            "importe" => "35.47",
        ])->for($transaccion)->for(Reserva::factory()->for($proyecto2), "pagable")
        ->create();

        $transaccion = Transaccion::factory([
            "fecha" => "2020-02-07",
            "moneda" => "BOB",
            "importe" => "3235.47",
            "estado" => 1
        ])->create();
        DetalleTransaccion::factory([
            "moneda" => "USD",
            "importe" => "50",
        ])->for($transaccion)->for(Venta::factory()->credito("10000")->for($proyecto2), "pagable")
        ->create();
        DetalleTransaccion::factory([
            "moneda" => "USD",
            "importe" => "414.86",
        ])->for($transaccion)
        ->for(Cuota::factory()
            ->for(Credito::factory()
                ->for(Venta::factory()->credito("9000")->for($proyecto1), "creditable")
            ),
        "pagable")
        ->create();
        DetalleTransaccion::factory([
            "moneda" => "BOB",
            "importe" => "0.04",
            "pagable_id" => 234,//$this->faker->randomNumber(),
            "pagable_type" => Account::class,
        ])->for($transaccion)/*->for(Account::factory([
            "moneda" => "BOB"
        ])->for($transaccion->cliente), "pagable")*/
        ->create();

    }

    it('genera el reporte de pagos de los ultimos 7 días', function () {
        /** @var TestCase $this */
        prepareData();
        // $this->travelTo(Carbon::createFromDate(2020, 1, 6));

        /** @var User $login */
        $login = User::factory()->create();
        $login->assignRole("super usuarios");
        
        $response = $this->actingAs($login)->getJson("api/reportes/reporte-de-pagos?desde=2019-12-31&hasta=2020-01-06");
        $response->assertOk();
        $response->assertJson([
            "description" => "31/12/2019 - 06/01/2020",
            "labels" => [
                "Mar. 31",
                "Mié. 01",
                "Jue. 02",
                "Vie. 03",
                "Sáb. 04",
                "Dom. 05",
                "Lun. 06"
            ],
            "datasets" => [
                [
                    "label" => "Reservas",
                    "data" => [ "0", "696", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Ventas al contado",
                    "data" => [ "0", "1000", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Ventas al credito",
                    "data" => [ "0", "6264", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Pago de cuotas",
                    "data" => [ "1770.7", "0", "0", "0", "0", "0", "0" ]
                ],
            ]
        ]);
        expect($response->json("download_link"))->not->toBeEmpty();
    });

    it('genera el reporte de pagos del mes en curso', function () {
        /** @var TestCase $this */
        prepareData();
        $this->travelTo(Carbon::createFromDate(2020, 1, 6));

        /** @var User $login */
        $login = User::factory()->create();
        $login->assignRole("super usuarios");
        
        $response = $this->actingAs($login)->getJson("api/reportes/reporte-de-pagos?desde=2020-01-01&hasta=2020-01-31");
        $response->assertOk();
        $response->assertJson([
            "description" => "01/01/2020 - 06/01/2020",
            "labels" => [
                "1º",
                "2º",
                "3º",
                "4º",
                "5º",
                "6º",
            ],
            "datasets" => [
                [
                    "label" => "Reservas",
                    "data" => [ "696", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Ventas al contado",
                    "data" => [ "1000", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Ventas al credito",
                    "data" => [ "6264", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Pago de cuotas",
                    "data" => [ "0", "0", "0", "0", "0", "0" ]
                ],
            ]
        ]);
        expect($response->json("download_link"))->not->toBeEmpty();
    });

    it('genera el reporte de pagos del mes anterior', function () {
        /** @var TestCase $this */
        prepareData();
        $this->travelTo(Carbon::createFromDate(2020, 1, 6));

        /** @var User $login */
        $login = User::factory()->create();
        $login->assignRole("super usuarios");
        
        $response = $this->actingAs($login)->getJson("api/reportes/reporte-de-pagos?desde=2019-12-01&hasta=2019-12-31");
        $response->assertOk();
        $response->assertJson([
            "description" => "01/12/2019 - 31/12/2019",
            "labels" => [ "1º", "2º", "3º", "4º", "5º", "6º", "7º", "8º", "9º", "10º",
                          "11º", "12º", "13º", "14º", "15º", "16º", "17º", "18º", "19º", "20º",
                          "21º", "22º", "23º", "24º", "25º", "26º", "27º", "28º", "29º", "30º", "31º"
            ],
            "datasets" => [
                [
                    "label" => "Reservas",
                    "data" => [ "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0",
                                "0" ]
                ],
                [
                    "label" => "Ventas al contado",
                    "data" => [ "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0",
                                "0" ]
                ],
                [
                    "label" => "Ventas al credito",
                    "data" => [ "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0",
                                "0" ]
                ],
                [
                    "label" => "Pago de cuotas",
                    "data" => [ "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0", 
                                "0", "0", "0", "0", "0", "0", "0", "0", "0", "0",
                                "1770.7" ]
                ],
            ]
        ]);
        expect($response->json("download_link"))->not->toBeEmpty();
    });

    it('genera el reporte de pagos del año en curso', function () {
        /** @var TestCase $this */
        prepareData();
        $this->travelTo(Carbon::createFromDate(2020, 3, 6));

        /** @var User $login */
        $login = User::factory()->create();
        $login->assignRole("super usuarios");
        
        $response = $this->actingAs($login)->getJson("api/reportes/reporte-de-pagos?desde=2020-01-01&hasta=2020-12-31");
        $response->assertOk();
        $response->assertJson([
            "description" => "01/01/2020 - 06/03/2020",
            "labels" => [
                "Enero",
                "Febrero",
                "Marzo",
            ],
            "datasets" => [
                [
                    "label" => "Reservas",
                    "data" => [ "942.87", "0", "0" ]
                ],
                [
                    "label" => "Ventas al contado",
                    "data" => [ "1000.00", "0", "0" ]
                ],
                [
                    "label" => "Ventas al credito",
                    "data" => [ "6264.00", "348.00", "0" ]
                ],
                [
                    "label" => "Pago de cuotas",
                    "data" => [ "1679.31", "2887.43", "0" ]
                ],
            ]
        ]);
        expect($response->json("download_link"))->not->toBeEmpty();
    });

    it('genera el reporte de pagos de otros años', function () {
        /** @var TestCase $this */
        prepareData();
        $this->travelTo(Carbon::createFromDate(2020, 3, 6));

        /** @var User $login */
        $login = User::factory()->create();
        $login->assignRole("super usuarios");
        
        $response = $this->actingAs($login)->getJson("api/reportes/reporte-de-pagos?desde=2016-01-01&hasta=2020-12-31");
        $response->assertOk();
        $response->assertJson([
            "description" => "01/01/2016 - 06/03/2020",
            "labels" => [
                "2016",
                "2017",
                "2018",
                "2019",
                "2020"
            ],
            "datasets" => [
                [
                    "label" => "Reservas",
                    "data" => [ "0", "0", "0", "0", "942.87"]
                ],
                [
                    "label" => "Ventas al contado",
                    "data" => [ "0", "0", "0", "0", "1000.00" ]
                ],
                [
                    "label" => "Ventas al credito",
                    "data" => [ "0", "0", "0", "0", "6612.00" ]
                ],
                [
                    "label" => "Pago de cuotas",
                    "data" => [ "0", "0", "0", "1770.70", "4566.74" ]
                ],
            ]
        ]);
        expect($response->json("download_link"))->not->toBeEmpty();
    });

    

    it('genera un reporte de pagos con fechas intermedias', function () {
        /** @var TestCase $this */
        prepareData();
        $this->travelTo(Carbon::createFromDate(2020, 1, 16));

        /** @var User $login */
        $login = User::factory()->create();
        $login->assignRole("super usuarios");
        
        $response = $this->actingAs($login)->getJson("api/reportes/reporte-de-pagos?desde=2020-01-11&hasta=2020-01-31");
        $response->assertOk();
        $response->assertJson([
            "description" => "11/01/2020 - 16/01/2020",
            "labels" => [
                "11º",
                "12º",
                "13º",
                "14º",
                "15º",
                "16º",
            ],
            "datasets" => [
                [
                    "label" => "Reservas",
                    "data" => [ "0", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Ventas al contado",
                    "data" => [ "0", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Ventas al credito",
                    "data" => [ "0", "0", "0", "0", "0", "0" ]
                ],
                [
                    "label" => "Pago de cuotas",
                    "data" => [ "0", "0", "1679.31", "0", "0", "0" ]
                ],
            ]
        ]);
        expect($response->json("download_link"))->not->toBeEmpty();
    });

    // test('elabora el reporte de pagos de un proyecto en particular', function () {
    //     // $response = $this->get('/reportepagos');
    
    //     // $response->assertStatus(200);
    //     expect(true)->toBeTrue();
    // });
});

