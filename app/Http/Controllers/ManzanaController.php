<?php

namespace App\Http\Controllers;

use App\Models\Manzana;
use Illuminate\Http\Request;

class ManzanaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
        
    }

    function index(Request $request, $proyectoId)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Manzana::where("proyecto_id", $proyectoId), $queryArgs);
    }
}
