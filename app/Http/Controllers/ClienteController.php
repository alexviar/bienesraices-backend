<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ClienteController extends Controller
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
        $this->authorize("viewAny", [Cliente::class, $request->all()]);
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Cliente::query(), $queryArgs);
    }

    function store(Request $request){
        $this->authorize("create", [Cliente::class, $request->all()]);
        $payload = $request->validate([
            "tipo" => "required|in:1,2",
            "tipo_documento" => ["required", "in:1,2", function($attribute, $value, $fail) use($request){
                if($request->input("tipo")==2 && $value == 1){
                    $fail("El carnet de identidad solo es valido para personas naturales.");
                }
            }],
            "numero_documento" => ["required", function($attribute, $value, $fail) use($request){
                if(($request->input("tipo_documento") == 1 && !preg_match("/^\d+(-[0-9][A-Z])?$/", $value)) ||
                   ($request->input("tipo_documento") == 2 && !is_integer($value))){
                    $fail("El número de documento es inválido.");
                }
                if(Cliente::where("tipo_documento", $request->input("tipo_documento"))->where("numero_documento", $value)->exists()){
                    $fail("Ya existe un cliente registrado con el documento proporcionado");
                }
            }],
            "apellido_paterno" => "required_if:apellido_materno,null,tipo,1",
            "apellido_materno" => "required_if:apellido_paterno,null,tipo,1",
            "nombre" => "required",
            "telefono" => "integer"
        ]);

        return Cliente::create($payload);
    }
}
