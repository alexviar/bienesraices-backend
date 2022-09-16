<?php

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\User;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

it('Responde con la lista de mora', function () {
    /** @var TestCase $this */
    $this->mock(UfvRepositoryInterface::class, function(MockInterface $mock){
        $mock->shouldReceive('findByDate')->andReturn(BigDecimal::one());
    });
    
    $this->travelTo(Carbon::createFromFormat("Y-m-d", "2020-09-01"));

    $cliente = Cliente::factory()->create();
    $credito = Credito::factory([
        "cuota_inicial" => "500",
        "plazo" => 48,
        "periodo_pago" => 1
    ])->for(Venta::factory([
        "fecha" => "2020-04-20",
        "moneda" => "BOB",
        "importe" => "500",
        "estado"=>1
    ])->for($cliente)->credito("10030.96"), "creditable")->create();
    $credito->build();
    Cuota::where("credito_id", $credito->id)->whereIn("numero", [1,2])->update([
        "saldo" => "0"
    ]);
    Cuota::where("credito_id", $credito->id)->where("numero", 3)->update([
        "saldo" => "155.11"
    ]);

    $response = $this->actingAs(User::find(1))->withHeaders(["Accept"=>"application/json"])->get('/api/lista-mora');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        "records" => [
            "*" => [
                "cliente" => [
                    "id",
                    "nombre_completo",
                    "documento_identidad" => [
                        "tipo",
                        "tipo_text",
                        "numero",
                    ]
                ],
                "resumen" => [
                    "BOB" => [
                        "saldo",
                        "multa",
                        "total"
                    ]
                ],
                "creditos" => [
                    "*" => [
                        "id",
                        "fecha",
                        "proyecto" => [
                            "id",
                            "nombre"
                        ],
                        "manzana" => [
                            "numero"
                        ],
                        "lote" => [
                            "numero"
                        ],
                        "cuotas_vencidas" => [
                            "*" => [
                                "vencimiento",
                                "numero",
                                "importe",
                                "saldo",
                                "multa",
                                "total"
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]);
});
