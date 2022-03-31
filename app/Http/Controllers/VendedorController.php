<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class VendedorController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
        if($search = Arr::get($queryArgs, "search")){
            $query->where(function($query) use($search) {
                $query->where("apellido_paterno", "LIKE", "$search%")
                      ->orWhere("apellido_materno", "LIKE", "$search%")
                      ->orWhere("nombre", "LIKE", "$search%");
            });
        }    
    }
    
    function index(Request $request)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Vendedor::query(), $queryArgs);
    }
}
