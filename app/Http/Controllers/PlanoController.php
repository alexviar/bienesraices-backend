<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanoController extends Controller
{
    function store(Request $request, $proyectoId)
    {
        $this->authorize("create", [Plano::class, $request->all()]);
        $proyecto = $this->findProyecto($proyectoId);
        $payload = $request->validate([
            "titulo" => "required|string|max:100",
            "descripcion" => "nullable|string|max:255",
            "manzanas" => "nullable|file|mimes:csv",
            "lotes" => "nullable|file|mimes:csv",
            "coordenadas" => "nullable|file|mimes:csv",
        ]);

        return DB::transaction(function () use ($payload, $proyecto) {
            if ($plano = $proyecto->plano) {
                $plano->is_vigente = false;
                $plano->update();
            }
            return $proyecto->plano()->create($payload);
        });
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
