<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LoteController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
        $filter = Arr::get($queryArgs, "filter", []);
        $search = Arr::get($queryArgs, "search", "");

        if($search){
            $query->where("numero", "like", "%$search%");
        }

        if($manzana_id = Arr::get($filter, "manzana_id")){
            $query->where("manzana_id", $manzana_id);
        }
    }

    function index(Request $request, $proyectoId)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Lote::whereHas("manzana", function($query) use($proyectoId){
            $query->where("proyecto_id", $proyectoId);
        }), $queryArgs);
    }
}
