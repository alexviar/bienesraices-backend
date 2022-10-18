<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    function findProyecto(Request $request, $id){
        $proyecto = Proyecto::find($id);
        if(!$proyecto){
            throw new ModelNotFoundException("El proyecto no existe");
        }
        return $proyecto;
    }

    function show(Request $request, $id)
    {
        return $this->findProyecto($request, $id);
    }

    function store(Request $request){
        $payload = $request->validate([
            "nombre" => "required|string",
            "ubicacion.latitud" => "required|numeric|min:-90|max:90",
            "ubicacion.longitud" => "required|numeric|min:-180|max:180",
            "moneda" => "required|exists:currencies,code",
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

    function update(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($request, $proyectoId);
        $payload = $request->validate([
            "nombre" => "sometimes|required|string",
            "ubicacion" => "sometimes|array|required",
            "ubicacion.latitud" => "required_with:ubicacion|numeric|min:-90|max:90",
            "ubicacion.longitud" => "required_with:ubicacion|numeric|min:-180|max:180",
            "moneda" => "sometimes|required|exists:currencies,code",
            "redondeo" => "sometimes|nullable|numeric",
            "precio_reservas" => "sometimes|required|numeric",
            "duracion_reservas" => "sometimes|required|integer",
            "cuota_inicial" => "sometimes|required|numeric",
            "tasa_interes" => "sometimes|required|numeric|min:0.0001|max:0.9999",
            "tasa_mora" => "sometimes|required|numeric|min:0.0001|max:0.9999",
        ]);
        if(Arr::has($payload, "ubicacion")){
            $payload["ubicacion"] = new Point(Arr::get($payload, "ubicacion.latitud"), Arr::get($payload, "ubicacion.longitud"));
        }
        $proyecto->update($payload);
        
        return $proyecto;
    }
}
