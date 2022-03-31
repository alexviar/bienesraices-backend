<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;

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
}
