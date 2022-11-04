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
        $this->authorize("viewAny", [Vendedor::class, $request->all()]);
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $query = Vendedor::query();
        if(($user = $request->user())->vendedor_id){
            $query->where("id", $user->vendedor_id);
        }
        return $this->buildResponse($query, $queryArgs);
    }

    function store(Request $request)
    {
        $this->authorize("create", [Vendedor::class, $request->all()]);
        $payload = $request->validate([
            "numero_documento" => "required",
            "apellido_paterno" => "required_if:apellido_materno,null",
            "apellido_materno" => "required_if:apellido_paterno,null",
            "nombre" => "required",
            "telefono" => "sometimes"
        ]);

        return Vendedor::create($payload);
    }
}
