<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class CajaController extends Controller
{
    function index(Request $request)
    {
        $queryArgs = $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Transaccion::with("detalles"), $queryArgs);
    }

    function store(Request $request)
    {
        if(is_array($request->detalles)) {
            $request->merge([
                "detalles" => array_map(function($detalle){
                    return [
                        "importe" => $detalle["importe"] ??  "0",
                        "cuota_id" => isset($detalle["cuota_id"]) ? Cuota::find($detalle["cuota_id"]) : null
                    ];
                }, $request->detalles)
            ]);
        }

        $now = now();
        $payload = $request->validate([
            "fecha" => "nullable|date|before_or_equal:".$now->format("Y-m-d"),
            "importe" => "required|numeric",
            "numero_transaccion" => "required|integer",
            "comprobante" => "required|image",
            "detalles" => ["array", function($attribute, $value, $fail) use($request){
                $detalles = collect($value);
                $deposito = BigDecimal::of($request->input("importe"))->toScale(2, RoundingMode::HALF_UP);
                $totalPagos = $detalles->reduce(function($carry, $item){
                    return $carry->plus($item["importe"]);
                }, BigDecimal::zero()->toScale(2, RoundingMode::HALF_UP));
                if($totalPagos->isGreaterThan($deposito)){
                    $fail("Los pagos exceden el monto depositado (Pagos: $totalPagos, Deposito: $deposito)");
                }
            }],
            "detalles.*.importe" => ["required", "numeric", function($attribute, $value, $fail) use($request, $now) {
                $cuota = $request->input(Str::replace("importe", "cuota_id", $attribute));
                if(!$cuota) return;
                $deuda = $cuota->calcularPago($request->fecha ? Carbon::createFromFormat("Y-m-d", $request->fecha)->startOfDay() : $now)->toScale(2, RoundingMode::HALF_UP);
                if(BigDecimal::of($value)->isGreaterThan($deuda)){
                    $fail("El pago excede el saldo de la cuota.");
                }
            }],
            "detalles.*.cuota_id" => [function($attribute, $value, $fail) use($now){
                if(!$value){
                    $fail("No es una cuota valida.");
                }
                else if($value->anteriorCuota && $now->lessThanOrEqualTo($value->anteriorCuota->vencimiento)){
                    $fail("Solo puede pagar cuotas en curso o vencidas.");
                }
            }],
        ], [
            "fecha.before_or_equal" => "El campo ':attribute' no puede ser posterior a la fecha actual.",
        ], [
            "detalles.*.importe" => "importe"
        ]);

        $head = Arr::except($payload, "detalles") + [ "fecha" => $now->format("Y-m-d") ];
        $details = $payload["detalles"];

        $transaccion = DB::transaction(function () use ($head, $details) {
            $transaccion = Transaccion::create([
                "comprobante" => $head["comprobante"]->store("comprobantes"),
                "moneda" => "BOB",
                "forma_pago" => 2,
            ] + $head);

            foreach ($details as $detail) {
                /** @var Cuota $transactable */
                $transactable = $detail["cuota_id"];
                $importe = (string) BigDecimal::of($detail["importe"])->toScale(2, RoundingMode::HALF_UP);
                $detailModel = new DetalleTransaccion();
                $detailModel->referencia = $transactable->getReferencia();
                $detailModel->moneda = $transactable->getCurrency()->code;
                $detailModel->importe = $importe;
                // $detailModel->transactable()->associate($transactable);

                $transaccion->detalles()->save($detailModel);

                $transactable->transacciones()->save($detailModel);
                $transactable->recalcular($importe, $transaccion->fecha);
                $transactable->save();
            }

            return $transaccion;
        });

        $transaccion->loadMissing("detalles");
        return $transaccion;
    }
}
