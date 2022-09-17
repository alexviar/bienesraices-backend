<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Reserva;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PagableController extends Controller
{
    function index(Request $request)
    {
        $codigoPago = $request->get("codigo_pago");
        if(!$codigoPago){
            abort(400, "No proporcionó un código de pago");
        }
        $fecha = $request->get("fecha");
        $fecha = $fecha ? Carbon::createFromFormat("Y-m-d", $fecha)->startOfDay() : Carbon::today();

        $cliente = Cliente::findByCodigoPago($codigoPago);

        //Reservas
        $reservas = Reserva::whereBelongsTo($cliente)
        ->where("estado", 1)
        ->where("saldo", ">", "0")
        ->oldest("id")
        ->get();

        //Ventas
        $ventas = Venta::whereBelongsTo($cliente)
        ->where("estado", 1)
        ->where("saldo", ">", "0")
        ->oldest("id")
        ->get();
        
        // //Creditos
        // $creditos = Credito::whereHasMorph("creditable", function($query) use($cliente){
        //     $query->whereBelongsTo($cliente);
        // })->where("estado", 1)
        // ->where("saldo", ">", "0")
        // ->get();

        //Cuotas
        $cuotas = Cuota::leftJoin("cuotas as anterior", function($join){
            $join->where("anterior.numero", DB::raw("cuotas.numero - 1"));
        })
        ->select("cuotas.*")
        ->where("cuotas.saldo", ">", "0")
        ->where(function($query) use($fecha) {
            $query->whereNull("anterior.vencimiento")
                ->orWhere("anterior.vencimiento", "<", $fecha);
        })
        ->whereHas("credito", function($query) use($cliente){
            $query->whereHasMorph("creditable", '*', function($query) use($cliente){
                $query->whereBelongsTo($cliente);
            });
            $query->where("estado", 1);
        })
        ->oldest("id")
        ->get();

        $cuotas->each->projectTo($fecha);

        $pagables = $reservas->map(function($reserva){
            return [
                "id" => $reserva->id,
                "type" => $reserva->getMorphClass(),
                "referencia" => $reserva->getReferencia(),
                "moneda" => $reserva->getCurrency()->code,
                "importe" => (string) $reserva->importe->amount,
                "saldo" => (string) $reserva->saldo->amount,
                "multa" => "0.0000",
                "total" => (string) $reserva->saldo->amount
            ];
        })->concat($ventas->map(function($venta){
            return [
                "id" => $venta->id,
                "type" => $venta->getMorphClass(),
                "referencia" => $venta->getReferencia(),
                "moneda" => $venta->getCurrency()->code,
                "importe" => (string) $venta->importe->amount,
                "saldo" => (string) $venta->saldo->amount,
                "multa" => "0.0000",
                "total" => (string) $venta->saldo->amount
            ];
        }))->concat($cuotas->map(function($cuota) use($fecha){
            return [
                "id" => $cuota->id,
                "type" => $cuota->getMorphClass(),
                "referencia" => $cuota->getReferencia(),
                "moneda" => $cuota->getCurrency()->code,
                "importe" => (string) $cuota->importe->amount,
                "saldo" => (string) $cuota->saldo->amount,
                "multa" => (string) $cuota->multa->amount,
                "total" => (string) $cuota->total->amount
            ];
        }))->toArray();

        return [
            "fecha" => $fecha->format("Y-m-d"),
            "cliente" => $cliente->setVisible(["id", "nombre_completo"]),
            "pagables" => $pagables
        ];
    }
}
