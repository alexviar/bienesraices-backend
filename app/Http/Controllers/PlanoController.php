<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PlanoController extends Controller
{
    function index(Request $request, $proyectoId){
        $proyecto = $this->findProyecto($proyectoId);
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse($proyecto->planos(), $queryArgs);
    }

    function store(Request $request, $proyectoId)
    {
        $this->authorize("create", [Plano::class, $request->all()]);
        $proyecto = $this->findProyecto($proyectoId);
        $payload = $request->validate([
            "titulo" => "required|string|max:100",
            "descripcion" => "nullable|string|max:255",
            "lotes" => "nullable|file|mimes:csv,txt",
        ]);

        return DB::transaction(function () use ($payload, $proyecto) {
            if ($plano = $proyecto->plano) {
                $plano->is_vigente = false;
                $plano->update();
            }
            $plano = $proyecto->plano()->create($payload);

            //Despachar un job?
            /** @var UploadedFile $csv */
            if($csv = Arr::get($payload, "lotes")){
                $plano->importManzanasYLotesFromCsv($csv->path());
            }

            return $plano;
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
