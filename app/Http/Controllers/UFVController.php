<?php

namespace App\Http\Controllers;

use App\Models\UFV;
use Illuminate\Http\Request;

class UFVController extends Controller
{
    function index(Request $request){
        $this->authorize("viewAny", [UFV::class, $request->all()]);
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(UFV::query()->latest("fecha"), $queryArgs);
    }

    function store(Request $request){
        $this->authorize("create", [UFV::class]);
        $payload = $request->validate([
            "fecha" => "required|date|unique:ufv",
            "valor" => "required|numeric"
        ], [
            "fecha.unique" => "Ya existe un registro en la fecha indicada."
        ]);

        return UFV::create($payload);
    }
}
