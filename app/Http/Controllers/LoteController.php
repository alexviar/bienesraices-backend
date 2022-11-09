<?php

namespace App\Http\Controllers;

use App\Models\CategoriaLote;
use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Proyecto;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class LoteController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
        $filter = Arr::get($queryArgs, "filter", []);
        $search = Arr::get($queryArgs, "search", "");

        if($search){
            $query->where("lotes.numero", "$search");
        }

        if($manzana_id = Arr::get($filter, "manzana_id")){
            $query->where("manzana_id", $manzana_id);
        }
        if($estado = Arr::get($filter, "estado")){
            $query->whereEstado($estado);
        }
    }

    private function findProyecto($proyectoId)
    {
        $proyecto = Proyecto::find($proyectoId);
        if(!$proyecto)
        {
            throw new ModelNotFoundException("El proyecto no existe");
        }
        return $proyecto;
    }

    function index(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        $this->authorize("viewAny", [Lote::class, $proyecto, $request->all()]);
        if(!($plano = $proyecto->plano)){
            // abort(404, "No hay un plano vigente.");
            return $this->buildPaginatedResponseData([
                "total_records" => 0
            ], []);
        }
        // else if(!$plano->is_locked){
        //     abort(403, "Las ediciones estan bloqueadas en el plano actual.");
        // }
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse($proyecto->plano->lotes()
        ->orderBy('manzanas.numero')
        ->orderBy('lotes.numero'), $queryArgs);
    }

    function store(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        if(!($plano = $proyecto->plano)){
            abort(404, "El proyecto no tiene un plano vigente.");
        }
        try
        {
            $request->merge([
                "geocerca" => Polygon::fromWKT($request->input("geocerca"))
            ]);
        }
        catch(Throwable $e){ }
        
        $this->authorize("create", [Lote::class, $proyecto, $request->all()]);
        // dd($proyecto->lotes()->where("error", 1)->whereNotExists(function($query){
        //     $query->select(DB::raw(1))
        //     ->from("lotes", "l")
        //     ->whereColumn("l.id", "<>", "id")
        //     ->whereRaw("ST_Area(St_Intersection(`l`.`geocerca`, `geocerca`)) = 0");
        // })->update(["error" => 0])->toSql());
        $payload = $request->validate([
            "numero" => ["required", Rule::unique(Lote::class)->where(function($query) use($request){
                $query->where("manzana_id", $request->input("manzana_id"));
            })],
            "superficie" => "required|numeric",
            "precio" => "nullable|numeric",
            "geocerca" => ["required", function($attribute, $value, $fail) {
                if(!($value instanceof Polygon))
                {
                    $fail("El campo '$attribute' no tiene un valor válido.");
                    return;
                }
                if(Lote::whereRaw("ST_Area(St_Intersection(`geocerca`, ST_GeomFromText(?))) > 0", [$value->toWKT()])->exists()){
                    $fail("La $attribute se sobrepone con otros lotes.");
                }
            }],
            "manzana_id" => ["required", Rule::exists(Manzana::class, "id")->where(function ($query) use($proyecto) {
                return $query->where("plano_id", $proyecto->plano->id);
            })],
            "categoria_id" => ["required", Rule::exists(CategoriaLote::class, "id")->where(function ($query) use($proyectoId) {
                return $query->where('proyecto_id', $proyectoId);
            })],
        ], [
            "numero.unique" => "La manzana indicada tiene un lote con el mismo número."
        ]);

        return Lote::create($payload);
    }
}
