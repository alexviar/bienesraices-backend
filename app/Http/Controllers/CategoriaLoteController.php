<?php

namespace App\Http\Controllers;

use App\Models\CategoriaLote;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaLoteController extends Controller
{
    function index(Request $request, $proyectoId)
    {
        $this->authorize("viewAll", [CategoriaLote::class, $request->all()]);
        $proyecto = $this->findProyecto($proyectoId);
        return $this->buildResponse($proyecto->categorias(), []);
    }

    function update(Request $request, $proyectoId, $categoriaId)
    {
        $categoria = $this->findCategoria($proyectoId, $categoriaId);
        $this->authorize("create", [$categoria, $request->all()]);
        $payload = $request->validate([
            "codigo" => ["required","string","max:4", function($attribute, $value, $fail) use($categoria){
                if($categoria->proyecto->categorias()->where("codigo", $value)->where("id", "<>", $categoria->id)->exists()){
                    $fail("El código esta repetido.");
                }
            }],
            "descripcion" => "nullable|string|max:255",
            "precio_m2" => "required|numeric",
        ]);

        return $categoria->update($payload);
    }

    function store(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        $this->authorize("create", [CategoriaLote::class, $request->all()]);
        $payload = $request->validate([
            "codigo" => ["required","string","max:4", function($attribute, $value, $fail) use($proyecto){
                if($proyecto->categorias()->where("codigo", $value)->exists()){
                    $fail("El código esta repetido.");
                }
            }],
            "descripcion" => "nullable|string|max:255",
            "precio_m2" => "required|numeric",
        ]);

        return $proyecto->categorias()->create($payload);
    }

    private function findCategoria($proyectoId, $categoriaId){
        $proyecto = Proyecto::find($proyectoId);
        $categoria = $proyecto->categorias()->firstWhere("id", $categoriaId);
        if (!$categoria) {
            throw new ModelNotFoundException("No existe una categoria con id '$categoriaId'");
        }
        return $categoria;
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
