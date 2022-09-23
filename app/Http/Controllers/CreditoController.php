<?php

namespace App\Http\Controllers;

use App\Http\Reports\Venta\HistorialPagos;
use App\Http\Reports\Venta\PlanPagosPdfReporter;
use App\Models\Credito;
use App\Models\PagoExtra;
use App\Models\Proyecto;
use App\Models\Services\ProgramadorPagoExtra;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditoController extends Controller
{

    function findProyecto($id){
        $proyecto = Proyecto::find($id);
        if(!$proyecto){
            throw new ModelNotFoundException("El proyecto no existe");
        }
        return $proyecto;
    }

    function findCredito($id){
        // $credito = Credito::with(["cuotas", "pagosExtras"])->find($id);
        $credito = Credito::with(["cuotas", "pagosExtras"])->where("codigo", $id)->where("estado", 1)->first();

        if(!$credito){
            throw new ModelNotFoundException("El credito no existe");
        }
        return $credito;
    }

    function show(Request $request, $id){
        return $this->findCredito($id);
    }

    function print_plan_pagos(Request $request, PlanPagosPdfReporter $reporter, $id){
        $credito = $this->findCredito($id);
        return $reporter->generate($credito)->stream("plan_pagos.pdf");
    }
    
    function print_historial_pagos(Request $request, HistorialPagos $report, $id){
        $credito = $this->findCredito($id);
        return $report->generate($credito)->stream("historial_pagos.pdf");
    }

    function store_pago_extra(Request $request, ProgramadorPagoExtra $programadorPagoExtra, $creditoId){
        $credito = $this->findCredito($creditoId);
        $payload = $request->validate([
            "tipo_ajuste" => "required|in:1,2,3,4",
            "importe" => "required|numeric",
            "periodo" => ["required", function($attribute, $value, $fail) use($request, $credito){
                if($credito->cuotas->contains(function($cuota) use($value){
                    return $cuota->numero > $value && $cuota->pago_extra->amount->isGreaterThan("0");
                })){
                    $fail("No puede programar un pago extra en el periodo indicado.");
                };
            }]
        ]);
        $this->authorize("create", [
            PagoExtra::class,
            $credito,
            $payload
        ]);

        $credito = DB::transaction(function() use($payload, $programadorPagoExtra, $credito){
            $credito->estado = 2;
            $credito->update();
            $pagoExtra = new PagoExtra($payload);
            // $credito->pagosExtras()->save($pagoExtra);
            $credito = $programadorPagoExtra->apply($credito, $pagoExtra); 
            return $credito;
        });

        return $credito;
    }

}
