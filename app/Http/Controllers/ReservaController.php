<?php

namespace App\Http\Controllers;

use App\Events\ReservaCreated;
use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Transaccion;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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
        return $this->buildResponse(Reserva::with(["cliente", "vendedor", "lote.manzana"])->where("proyecto_id", $proyectoId)->latest("updated_at"), $queryArgs);
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
            "saldo_contado" => "required|numeric",
            "saldo_credito" => "required|numeric",
            "vencimiento" => "required|date"
        ]);

        $payload["importe"] = (string) BigDecimal::of($payload["importe"])->toScale(2, RoundingMode::HALF_UP);
        $payload["saldo_contado"] = (string) BigDecimal::of($payload["saldo_contado"])->toScale(2, RoundingMode::HALF_UP);
        $payload["saldo_credito"] = (string) BigDecimal::of($payload["saldo_credito"])->toScale(2, RoundingMode::HALF_UP);

        $reserva = DB::transaction(function() use($payload, $proyectoId, $request){
            $reserva = Reserva::create($payload+[
                "proyecto_id" => $proyectoId
            ]);

            ReservaCreated::dispatch($reserva, $request->user()->id);

            return $reserva;
        });
        
        return $reserva;
    }
}
