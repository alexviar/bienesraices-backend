<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Log;

class ListaMoraController extends Controller
{

    function applyFilters($query, $queryArgs)
    {
        $query->whereHas("creditosEnMora", function($subquery){

        });
    }

    function index(Request $request)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $response = $this->buildResponse(Cliente::query()->with(Arr::dot(["creditosEnMora"=>[
            "cuotasVencidas",
            "proyecto",
            "lote"
        ]])), $queryArgs);

        $response["records"] = $response["records"]->map(function($value) {
            $resumen = $value->creditosEnMora->reduce(function($carry, $credito){
                return $credito->cuotasVencidas->reduce(function($carry, $value) {
                    $saldo = BigDecimal::of($value->saldo->amount)->plus($carry[$value->getCurrency()->code]["saldo"] ?? "0");
                    $multa = BigDecimal::of($value->multa->amount)->plus($carry[$value->getCurrency()->code]["multa"] ?? "0");
                    $total = BigDecimal::of($value->total->amount)->plus($carry[$value->getCurrency()->code]["total"] ?? "0");
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
                    "nombre_completo",
                    "documento_identidad",
                    "telefono"
                ])->toArray(),
                "resumen" => $resumen,
                "creditos" => $value->creditosEnMora->append("manzana")->each->setVisible([
                    "id",
                    "fecha",
                    "proyecto"=>[
                        "id",
                        "nombre"
                    ],
                    "lote"=>[
                        "numero"
                    ],
                    "manzana"=>[
                        "numero"
                    ],
                    "cuotasVencidas"=>[
                        "*" => [
                            "numero",
                            "vencimiento",
                            "importe",
                            "saldo",
                            "multa",
                            "total"
                        ]
                    ]
                ])->toArray()
            ];
        });

        return $response;
    }
}
