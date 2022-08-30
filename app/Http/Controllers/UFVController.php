<?php

namespace App\Http\Controllers;

use App\Models\UFV;
use Illuminate\Http\Request;

class UFVController extends Controller
{
    function index(Request $request){
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(UFV::query()->latest("fecha"), $queryArgs);
    }

    function store(Request $request){
        $payload = $request->validate([
            "fecha" => "required|date|unique:ufv",
            "valor" => "required|numeric"
        ], [
            "fecha.unique" => "Ya existe un registro en la fecha indicada."
        ]);

        return UFV::create($payload);
    }
}
