<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProyectoController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
    }

    function index(Request $request)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Proyecto::query(), $queryArgs);
    }

    function show(Request $request, $id)
    {
        return Proyecto::find($id);
    }

    function store(Request $request){
        $payload = $request->validate([
            "nombre" => "required|string",
            "ubicacion.latitud" => "required|numeric|min:-90|max:90",
            "ubicacion.longitud" => "required|numeric|min:-180|max:180",
            "moneda" => "required|exists:currencies,code",
            "precio_mt2" => "required|numeric",
            "redondeo" => "nullable|numeric",
            "precio_reservas" => "required|numeric",
            "duracion_reservas" => "required|integer",
            "cuota_inicial" => "required|numeric",
            "tasa_interes" => "required|numeric|min:0.0001|max:0.9999",
            "tasa_mora" => "required|numeric|min:0.0001|max:0.9999",
        ]);

        $proyecto = DB::transaction(function() use($payload) {
            $proyecto = Proyecto::create([
                "ubicacion" => new Point(Arr::get($payload, "ubicacion.latitud"), Arr::get($payload, "ubicacion.longitud"))
            ] + $payload);
            // $proyecto->planos()->create(["descripcion" => "Plano inicial"]);
            return $proyecto;
        });

        return $proyecto;
    }
}
