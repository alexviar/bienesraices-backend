<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    function index(Request $request)
    {
        $queryArgs = $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Transaccion::with("detalles"), $queryArgs);
    }

    function store(Request $request)
    {
        $payload = $request->validate([
            "fecha" => "date",
            "importe" => "numeric",
            "numero_transaccion" => "integer",
            "comprobante" => "mimes:jpg",
            "detalles" => "array",
            // "detalles.*.referencia" => "string", //¿Debería volver a generarse en el servidor en lugar de confiar en el frontend? <-> ¿Podría en algunos casos estar definda por el usuario?
            "detalles.*.transactable_id" => "numeric",
            "detalles.*.transactable_type" => "in:" . Cuota::class,
            "detalles.*.importe" => "numeric"
        ]);

        $head = Arr::except($payload, "detalles");
        $details = $payload["detalles"];

        $transaccion = DB::transaction(function () use ($head, $details) {
            $transaccion = Transaccion::create([
                "comprobante" => $head["comprobante"]->store("comprobantes"),
                "moneda" => "BOB",
                "forma_pago" => 2,
            ] + $head);

            foreach ($details as $detail) {
                $transactableType = $detail["transactable_type"];
                $transactableId = $detail["transactable_id"];
                $transactable =  $transactableType::find($transactableId);
                if (!$transactable) throw new Exception("Transactable de tipo {$transactableType} e id {$transactableId} no existe");

                $detailModel = new DetalleTransaccion();
                $detailModel->referencia = $transactable->getReferencia();
                $detailModel->moneda = $transactable->getCurrency()->code;
                $detailModel->importe = (string) BigDecimal::of($detail["importe"])->toScale(2, RoundingMode::HALF_UP);
                $detailModel->transactable()->associate($transactable);

                $transaccion->detalles()->save($detailModel);
                
                $transactable->recalcularSaldo($detailModel->importe, $transaccion->fecha);
                $transactable->save();
            }

            return $transaccion;
        });

        $transaccion->loadMissing("detalles");
        return $transaccion;
    }
}
