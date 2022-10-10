<?php

namespace App\Http\Controllers;

use App\Models\Manzana;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ManzanaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
        if($search = Arr::get($queryArgs, "search"))
        {
            $query->where("numero", $search);
        }
    }

    function index(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        if(!$proyecto->plano){
            return $this->buildPaginatedResponseData([
                "total_records" => 0
            ], []);
        }
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $response = $this->buildResponse($proyecto->plano->manzanas()->orderBy("numero"), $queryArgs);
        $response["records"]->each->append("total_lotes");
        return $response;
    }

    private function findProyecto($proyectoId)
    {
        $proyecto = Proyecto::find($proyectoId);
        if(!$proyecto)
        {
            throw new ModelNotFoundException("El proyecto no existe");
        }
        return $proyecto;
    }

    function store(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        if(!($plano = $proyecto->plano)){
            abort(404, "El proyecto no tiene un plano vigente.");
        }
        $payload = $request->validate([
            "numero" => ["required", Rule::unique(Manzana::class)->where(function ($query) use($plano) {
                return $query->where("plano_id", $plano->id);
            })],
        ], [
            "numero.unique" => "Ya ha registrado una manzana con el mismo número."
        ]);

        return $proyecto->plano->manzanas()->create($payload);
    }
}
