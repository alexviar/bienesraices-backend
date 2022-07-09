<?php

namespace App\Http\Controllers;

use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Transaccion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
 
    }

    function index(Request $request, $proyectoId)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Reserva::with(["cliente", "vendedor", "lote.manzana"])->where("proyecto_id", $proyectoId), $queryArgs);
    }

    function store(Request $request, $proyectoId){
        $payload = $request->validate([
            "fecha" => "required|date",
            "lote_id" => ["required", function ($attribute, $value, $fail) use($proyectoId){
                $lote = Lote::find($value);
                if(!$lote || $lote->proyecto->id != $proyectoId){
                    $fail('Lote inválido.');
                }
                else if($lote->estado["code"] !== 1){
                    $fail('El lote no esta disponible.');
                }
            }],
            "cliente_id" => "required|exists:clientes,id",
            "vendedor_id" => "required|exists:vendedores,id",
            "moneda" => "required|exists:currencies,code",
            "importe" => "required|numeric",
            // "precio" => "string",
            // "cuota_inicial" => "string",
            "vencimiento" => "required|date"
        ]);

        $reserva = DB::transaction(function() use($payload, $proyectoId){
            $reserva = Reserva::create($payload+[
                // "saldo_credito" => $payload["cuota_inicial"],
                // "saldo_contado" => $payload["precio"],
                "proyecto_id" => $proyectoId
            ]);
            $reserva->refresh();
            
            $transaccion = Transaccion::create([
                "fecha" => Carbon::now(),
                "moneda" => $reserva->moneda,
                "importe" => $reserva->importe->amount,
                "forma_pago" => 1,
            ]);
            $detailModel = new DetalleTransaccion();
            $detailModel->referencia = $reserva->getReferencia();
            $detailModel->moneda = $reserva->getCurrency()->code;
            $detailModel->importe = $reserva->importe->amount;
            $detailModel->transactable()->associate($reserva);
    
            $transaccion->detalles()->save($detailModel);
        });
        
        return $reserva;
    }
}
