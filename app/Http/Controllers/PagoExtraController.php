<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\Proyecto;
use App\Models\Services\ProgramadorPagoExtra;
use App\Models\Venta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoExtraController extends Controller
{

    function findProyecto($id){
        $proyecto = Proyecto::find($id);
        if(!$proyecto){
            throw new ModelNotFoundException("El proyecto no existe");
        }
        return $proyecto;
    }

    function findCredito($id){
        // $venta = Venta::with("credito")->find($id);
        $credito = Credito::with(["cuotas", "pagosExtras"])->find($id);

        if(!$credito){
            throw new ModelNotFoundException("El credito no existe");
        }
        return $credito;
    }

    // /**
    //  * @return PagoExtraStrategy
    //  */
    // function getPagoExtraStrategy($tipo_ajuste){
    //     switch($tipo_ajuste){
    //         case 1:
    //             return new Prorrateo();
    //         case 2:
    //             return new DisminucionPlazo();
    //         case 3:
    //             return new SoloInteres(false);
    //         case 4:
    //             return new SoloInteres(true);
    //     }
    // }

    function store(Request $request, ProgramadorPagoExtra $programadorPagoExtra, $creditoId){
        $credito = $this->findCredito($creditoId);

        $payload = $request->validate([
            "tipo_ajuste" => "required|in:1,2,3,4",
            "importe" => "required|numeric",
            "periodo" => "required"
        ]);

        $pagoExtra = DB::transaction(function() use($payload, $programadorPagoExtra, $credito){
            $pagoExtra = $programadorPagoExtra->apply($credito, $payload["importe"], $payload["periodo"], $payload["tipo_ajuste"]); 
            $credito->cuotas->each->update();
            return $credito->pagosExtras()->save($pagoExtra);
        });

        return $pagoExtra;
    }

}
