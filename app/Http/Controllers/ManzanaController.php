<?php

namespace App\Http\Controllers;

use App\Models\Manzana;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $response = $this->buildResponse(Manzana::where("proyecto_id", $proyectoId)->latest(), $queryArgs);
        $response["records"]->each->append("total_lotes");
        return $response;
    }

    function store(Request $request, $proyectoId)
    {
        $proyecto = Proyecto::find($proyectoId);
        if(!$proyecto)
        {
            throw new ModelNotFoundException("El proyecto no existe");
        }
        $payload = $request->validate([
            "numero" => ["required", function($attribute, $value, $fail) use($proyecto){
                if($proyecto->manzanas()->where("numero", $value)->exists())                {
                    $fail("Ya ha registrado una manzana con el mismo número.");
                }
            }]
        ]);

        return $proyecto->manzanas()->create($payload);
    }
}
