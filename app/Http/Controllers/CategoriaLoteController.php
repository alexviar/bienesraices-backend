<?php

namespace App\Http\Controllers;

use App\Models\CategoriaLote;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CategoriaLoteController extends Controller
{
    function index(Request $request, $proyectoId)
    {
        $this->authorize("viewAll", [CategoriaLote::class, $request->all()]);
        $proyecto = $this->findProyecto($proyectoId);
        return $this->buildResponse($proyecto->categorias(), []);
    }

    function store(Request $request, $proyectoId)
    {
        $this->authorize("create", [CategoriaLote::class, $request->all()]);
        $proyecto = $this->findProyecto($proyectoId);
        $payload = $request->validate([
            "codigo" => "required|string|max:4",
            "descripcion" => "nullable|string|max:255",
            "precio_m2" => "required|numeric",
        ]);

        return $proyecto->categorias()->create($payload);
    }

    private function findProyecto($proyectoId)
    {
        $proyecto = Proyecto::find($proyectoId);
        if (!$proyecto) {
            throw new ModelNotFoundException("No existe un proyecto con id '$proyectoId'");
        }
        return $proyecto;
    }
}
