<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Transaccion;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        try{
            $request->merge([
                "detalles" => array_map(function($detalle){
                    return Arr::only($detalle, ["importe", "cuota_id"]) + [
                        "cuota" => isset($detalle["cuota_id"]) ? Cuota::find($detalle["cuota_id"]) : null
                    ];
                }, $request->detalles)
            ]);
        }
        catch(Throwable $t){ }

        return $request->validate([
            "fecha" => "nullable|date|before_or_equal:".$now->format("Y-m-d"),
            "moneda" => "required",
            "importe" => "required",
            "numero_transaccion" => "required|integer",
            "comprobante" => "required|image",

            "detalles" => ["required", "array", function($attribute, $value, $fail) use($request){
                try{
                    $detalles = collect($value);
                    $deposito = BigDecimal::of($request->input("importe"))->toScale(2, RoundingMode::HALF_UP);
                    $totalPagos = $detalles->reduce(function($carry, $item){
                        return $carry->plus($item["importe"]);
                    }, BigDecimal::zero()->toScale(2, RoundingMode::HALF_UP));
                    if($totalPagos->isGreaterThan($deposito)){
                        // $fail("Los pagos exceden el monto depositado (Pagos: $totalPagos, Deposito: $deposito)");
                        $fail("Los pagos exceden el monto depositado.");
                    }
                }
                catch(Throwable $t){}
            }],
            "detalles.*.importe" => ["required", "numeric", function($attribute, $value, $fail) use($request, $now) {
                /** @var Cuota $cuota */
                $cuota = $request->input(Str::replace("importe", "cuota", $attribute));
                
                if(!$cuota) return;

                $cuota->projectTo($request->fecha ? 
                    Carbon::createFromFormat("Y-m-d", $request->fecha)->startOfDay() : 
                    $now
                );
                $deuda = $cuota->total->round(2)->amount;

                if(BigDecimal::of($value)->isGreaterThan($deuda)){
                    $fail("El pago excede el saldo de la cuota.");
                }
            }],
            "detalles.*.cuota_id" => ["required", function($attribute, $value, $fail) use($request){
                $cuota = $request->input(Str::replace("cuota_id", "cuota", $attribute));
                if(!$cuota){
                    $fail("No existe una cuota con el id proporcionado.");
                }
                else if(!$cuota->pendiente){
                    $fail("Solo puede registrar pagos de cuotas pendientes.");
                }
            }]
        ], [
            "fecha.before_or_equal" => "El campo ':attribute' no puede ser posterior a la fecha actual.",
        ], [
            "detalles.*.importe" => "importe",
        ]);
    }

    function pagar_cuotas(Request $request){
        
        $payload = $this->validatePagoRequest($request);

        $transaccion = DB::transaction(function() use($payload){
            $detalles = $payload["detalles"];
            $fecha = Arr::get($payload, "fecha", now()->format("Y-m-d"));

            $transaccion = Transaccion::create(Arr::only($payload, [
                "fecha", "moneda", "importe"
            ]) + [
                "fecha" => $fecha,
                "forma_pago" => 2
            ]);
            
            foreach($detalles as $detalle){
                /** @var Cuota $cuota */
                $cuota = $detalle["cuota"];
                $cuota->recalcular($detalle["importe"], Carbon::createFromFormat("Y-m-d", $fecha));
                $cuota->save();
            }

            return $transaccion;
        });

        return $transaccion;
    }
}