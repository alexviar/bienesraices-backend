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
        $this->authorize("viewAll", [Plano::class, $queryArgs]);
        return $this->buildResponse($proyecto->planos(), $queryArgs);
    }

    function show(Request $request, $proyectoId, $planoId){
        $plano = $this->findPlano($proyectoId, $planoId);
        $this->authorize("view", [$plano]);
        return $plano;
    }

    function store(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        $this->authorize("create", [Plano::class, $request->all()]);
        $payload = $request->validate([
            "titulo" => "required|string|max:100",
            "descripcion" => "nullable|string|max:255",
            "lotes" => "nullable|file|mimes:csv,txt",
        ]);

        $plano = DB::transaction(function () use ($payload, $proyecto) {
            if ($plano = $proyecto->plano) {
                $plano->is_vigente = false;
                $plano->is_locked = true;
                $plano->update();
            }
            return $proyecto->plano()->create($payload + [
                "import_warnings" => Arr::has($payload, "lotes") ? "In progress" :  null
            ]);
        });
        
        //Despachar un job?
        /** @var UploadedFile $csv */
        if($csv = Arr::get($payload, "lotes")){
            $plano->importManzanasYLotesFromCsv($csv->path());
        }

        return $plano;
    }

    function update(Request $request, $proyectoId, $planoId)
    {
        $plano = $this->findPlano($proyectoId, $planoId);
        $this->authorize("update", [$plano, $request->all()]);
        $payload = $request->validate([
            "titulo" => "required|string|max:100",
            "descripcion" => "nullable|string|max:255",
            "lotes" => "nullable|file|mimes:csv,txt",
        ]);

        $plano->update($payload + [
            "import_warnings" => Arr::has($payload, "lotes") ? "In progress" :  null
        ]);

        //Despachar un job?
        /** @var UploadedFile $csv */
        if($csv = Arr::get($payload, "lotes")){
            $plano->importManzanasYLotesFromCsv($csv->path());
        }

        return $plano;
    }

    private function findProyecto($proyectoId)
    {
        $proyecto = Proyecto::find($proyectoId);
        if (!$proyecto) {
            throw new ModelNotFoundException("No existe un proyecto con id '$proyectoId'");
        }
        return $proyecto;
    }

    private function findPlano($proyectoId, $planoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        $plano = $proyecto->planos()->firstWhere("id", $planoId);
        if (!$plano) {
            throw new ModelNotFoundException("No existe un plano con id '$planoId'");
        }
        return $plano;
    }
}
