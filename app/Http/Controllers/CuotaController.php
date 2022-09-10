<?php

namespace App\Http\Controllers;

use App\Events\PagoCuotaCreated;
use App\Jobs\RegistrarTransaccion;
use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\SaldoFavor;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CuotaController extends Controller
{

    function pendientes(Request $request){
        $codigoPago = $request->get("codigo_pago");
        if(!$codigoPago){
            abort(400, "No proporcionó un código de pago");
        }
        $fecha = $request->get("fecha");
        $fecha = $fecha ? Carbon::createFromFormat("Y-m-d", $fecha)->startOfDay() : Carbon::today();

        $cliente = Cliente::findByCodigoPago($codigoPago);

        if(!$cliente){
            throw new ModelNotFoundException("No existe un cliente con el código de pago dado.");
        }

        $creditos =Credito::with("cuotas")->where("estado", 1)->whereHasMorph("creditable", [Venta::class], function($query) use($cliente){
            $query->where("cliente_id", $cliente->id);
        })->get();

        return [
            "fecha" => $fecha->format("Y-m-d"),
            "cliente" => $cliente->setVisible(["id", "nombre_completo"]),
            "cuotas" => $creditos->each(function($credito) use($fecha){
                $credito->projectTo($fecha);
            })->pluck("cuotas_pendientes")->flatten()->map(function($cuota) use($fecha){
                return [
                    "id" => $cuota->id,
                    "referencia" => $cuota->getReferencia(),
                    "moneda" => $cuota->getCurrency()->code,
                    "importe" => (string) $cuota->importe->amount,
                    "saldo" => (string) $cuota->saldo->amount,
                    "multa" => (string) $cuota->multa->amount,
                    "total" => (string) $cuota->total->amount
                ];
            })
        ];
    }

    function validatePagoRequest(Request $request) {
        $now = Carbon::today();
        $request->merge([
            "fecha" => $request->fecha ?? $now->format("Y-m-d"),
        ]);

        $payload = $request->validate([
            "fecha" => "date|before_or_equal:".$now->format("Y-m-d"),
            "importe" => ["required", "numeric"]
        ], [
            "fecha.before_or_equal" => "El campo ':attribute' no puede ser posterior a la fecha actual.",
        ]);
        $payload["importe"] = (string) BigDecimal::of($payload["importe"])->toScale(2, RoundingMode::HALF_UP);
        return $payload;
    }

    function findCuota($id){
        $cuota = Cuota::find($id);
        if(!$cuota){
            throw new ModelNotFoundException("No existe una cuota con id '$id'");
        }
        return $cuota;
    }

    function pagar(Request $request, $id){
        $cuota = $this->findCuota($id);
        $payload = $this->validatePagoRequest($request);
        $cuota->projectTo(Carbon::createFromFormat("Y-m-d", $payload["fecha"]));
        if($cuota->total->amount->isLessThan($payload["importe"])){
            throw ValidationException::withMessages(["importe"=>["El pago excede el saldo de la cuota."]]);
        }

        $this->authorize("pagar", [$cuota, $payload]);

        $transaccion = DB::transaction(function() use($payload, $cuota, $request){
            $pago = $cuota->pagos()->create(Arr::only($payload, [
                "fecha",
                "importe"
            ]) + [
                "moneda" => $cuota->getCurrency()->code
            ]);

            PagoCuotaCreated::dispatch($pago, $request->user()->id);

            $cuota->load("pagos");
            $cuota->recalcularSaldo();
            $cuota->total_pagos = (string) $cuota->total_pagos->amount->plus($payload["importe"]);
            $cuota->update();

            // $this->dispatchSync(new RegistrarTransaccion(Arr::only($payload, [
            //     "fecha",
            //     "importe",
            //     "observaciones",
            //     "metodo_pago",
            //     "deposito"
            // ]) + [
            //     "moneda" => $cuota->getCurrency()->code,
            //     "transactable_id" => $cuota->transactable_id,
            //     "transactable_type" => Cuota::class,
            //     "referencia" => $cuota->getReferencia(),
            //     //Ley de Demeter?
            //     "cliente_id" => $cuota->credito->creditable->cliente_id
            // ]));
        });

        return $transaccion;
    }
}