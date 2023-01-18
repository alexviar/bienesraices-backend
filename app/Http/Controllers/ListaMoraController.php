<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ListaMoraController extends Controller
{

    function applyFilters($query, $queryArgs)
    {
        $query->with(Arr::dot(["creditosEnMora"=>[
            "credito.cuotasVencidas",
            "proyecto",
            "lote"
        ]]));
        $sub = Venta::selectRaw("MIN(cuotas.vencimiento) as fecha")
            ->addSelect("cliente_id")
            ->join("creditos", "creditos.creditable_id", "ventas.id")
            ->join("cuotas", "cuotas.credito_id", "creditos.id")
            ->where("ventas.estado", 1)
            ->where("creditos.estado", 1)
            ->where("cuotas.vencimiento", "<", Carbon::today()->toDateString())
            ->where("cuotas.saldo", ">", 0)
            ->groupBy("cliente_id");
        $query->joinSub($sub, "sub1", function($query){
            $query->on("sub1.cliente_id", "clientes.id");
        });
        // $query->whereHas("creditosEnMora", function($subquery){

        // });

        $query->select("clientes.*");
        $query->orderBy("sub1.fecha");
    }

    function index(Request $request)
    {
        $this->authorize("viewListaMora", [Cliente::class, $request->all()]);
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $response = $this->buildResponse(Cliente::query(), $queryArgs);

        $response["records"] = $response["records"]->map(function($value) {
            $resumen = $value->creditosEnMora->reduce(function($carry, $venta){
                return $venta->credito->cuotasVencidas->reduce(function($carry, $value) {
                    $saldo = $value->saldo->amount->plus($carry[$value->getCurrency()->code]["saldo"] ?? "0");
                    $multa = $value->multa->amount->plus($carry[$value->getCurrency()->code]["multa"] ?? "0");
                    $total = $value->total->amount->plus($carry[$value->getCurrency()->code]["total"] ?? "0");
                    $carry[$value->getCurrency()->code] = [
                        "saldo" => $saldo,
                        "multa" => $multa,
                        "total" => $total,
                    ];
                    return $carry;
                }, $carry);
            }, []);

            return [
                "cliente" => $value->setVisible([
                    "id",
                    "nombre",
                    "apellido_paterno",
                    "apellido_materno",
                    "nombre_completo",
                    "documento_identidad",
                    "telefono"
                ])->toArray(),
                "resumen" => $resumen,
                // "creditos" => $value->creditosEnMora->append("manzana")->each->setVisible([
                //     "id",
                //     "fecha",
                //     "proyecto"=>[
                //         "id",
                //         "nombre"
                //     ],
                //     "lote"=>[
                //         "numero"
                //     ],
                //     "manzana"=>[
                //         "numero"
                //     ],
                //     "cuotasVencidas"=>[
                //         "*" => [
                //             "numero",
                //             "vencimiento",
                //             "importe",
                //             "saldo",
                //             "multa",
                //             "total"
                //         ]
                //     ]
                // ])->toArray()
                "creditos" => $value->creditosEnMora->map(function($venta){
                    return [
                        "id" => $venta->credito->codigo,
                        "fecha" => $venta->fecha->format("Y-m-d"),
                        "proyecto" => [
                            "id" => $venta->proyecto->id,
                            "nombre" => $venta->proyecto->nombre
                        ],
                        "lote" => [
                            "numero" => $venta->lote->numero
                        ],
                        "manzana" => [
                            "numero" => $venta->manzana->numero
                        ],
                        "cuotas_vencidas" => $venta->credito->cuotasVencidas->map(function($cuota){
                            return $cuota->setVisible([
                                "numero",
                                "vencimiento",
                                "importe",
                                "saldo",
                                "multa",
                                "total"
                            ]);
                        })
                    ];
                })
            ];
        });

        return $response;
    }
}
