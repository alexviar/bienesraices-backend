<?php

namespace App\Http\Controllers;

use App\Events\TransaccionRegistrada;
use App\Models\Account;
use App\Models\Cuota;
use App\Models\Reserva;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CajaController extends Controller
{
    function index(Request $request)
    {
        $queryArgs = $request->only(["search", "filter", "page"]);
        $this->authorize("viewAny", [Transaccion::class, $queryArgs]);
        return $this->buildResponse(Transaccion::with(["anulacion", "cliente"])->latest(), $queryArgs);
    }

    function show(Request $request, $id)
    {
        $transaccion = $this->findTransaccion($id);
        $this->authorize("view", [$transaccion]);
        return $transaccion->loadMissing(["detalles", "detallesPago", "cliente"]);
    }

    function comprobante(Request $request, $comprobante)
    {
        if (Storage::exists("comprobantes/$comprobante")) {
            return Storage::download("comprobantes/$comprobante", null, [
                'Content-Type' => Storage::mimeType("comprobantes/$comprobante"),
                'Content-Disposition' => 'inline; filename="' . $comprobante . '"'
            ]);
        }
        abort(404);
    }

    function store(Request $request)
    {
        $today = Carbon::today();
        $request->mergeIfMissing([
            "fecha" => $today->format("Y-m-d")
        ]);
        $this->authorize("create", [Transaccion::class, $request->all()]);

        //Mejor no usar la validacion exists para disminuir el numero de llamadas a la BD
        //¿Que tan conveniente es dejar que la solicitud falle?
        $payload = $request->validate([
            "fecha" => "date|before_or_equal:" . $today->format("Y-m-d"),
            "cliente_id" => "required",
            "moneda" => "required|exists:currencies,code",
            "registrar_excedentes" => "nullable|boolean",

            "detalles" => "required|array",
            "detalles.*.id" => "required",
            "detalles.*.type" => "required|in:" . Reserva::class . "," . Venta::class . "," . Cuota::class,
            "detalles.*.importe" => "required|numeric",

            "medios_pago" => "required|array",
            "medios_pago.*.forma_pago" => "required|in:1,2",
            "medios_pago.*.importe" => "required|numeric",
            "medios_pago.*.comprobante" => "required_if:medios_pago.*.forma_pago,2|image",
            "medios_pago.*.numero_comprobante" => "required_if:medios_pago.*.forma_pago,2|numeric"
        ], [
            "fecha.before_or_equal" => "El campo 'fecha' debe ser anterior o igual a la fecha actual."
        ], [
            "detalles.*.type" => "tipo",
            "moneda" => "moneda de pago",
            "medios_pago" => "medios de pago",

            "detalles.*.id" => "id",
            "detalles.*.type" => "tipo",
            "detalles.*.importe" => "importe",

            "medios_pago.*.forma_pago" => "forma de pago",
            "medios_pago.*.importe" => "importe",
            "medios_pago.*.comprobante" => "comprobante",
            "medios_pago.*.numero_comprobante" => "n.º de comprobante"
        ]);

        $transaccion = DB::transaction(function () use ($payload, $request) {
            $transaccion = Transaccion::create(Arr::except($payload, ["medios_pago", "detalles"]) + [
                "user_id" => $request->user()->id,
            ]);

            // $importeTotal = new Money("0", $payload["moneda"]);
            $detalles = collect($payload["detalles"])->sortBy(["id", "type"]);
            foreach ($detalles as $key => $detalle) {
                $pagable = $this->findPagable($detalle["type"], $detalle["id"]);

                $importe = new Money($detalle["importe"], $pagable->getCurrency()->code);
                $importe->round(2);
                $transaccion->importe = $transaccion->importe->plus($importe->exchangeTo($transaccion->moneda, [
                    "date" => $transaccion->fecha
                ]))->amount;

                $transaccion->detalles()->create([
                    "moneda" => $pagable->getCurrency()->code,
                    "importe" => $importe->amount,
                    "referencia" => $pagable->getReferencia(),
                    "pagable_id" => $pagable->getMorphKey(),
                    "pagable_type" => $pagable->getMorphClass()
                ]);
            }
            $transaccion->importe = $transaccion->importe->round(2)->amount;
            $transaccion->update();

            $pagoTotal = BigDecimal::zero();
            foreach ($payload["medios_pago"] as $key => $pago) {
                $pagoTotal = $pagoTotal->plus($pago["importe"])->toScale(2, RoundingMode::HALF_UP);
                if ($comprobante = Arr::get($pago, "comprobante")) {
                    $path = $comprobante->store("comprobantes");
                    $pago["comprobante"] = $path;
                }
                $transaccion->detallesPago()->create(Arr::only($pago, ["forma_pago", "importe", "numero_comprobante", "comprobante"]) + [
                    "moneda" => $transaccion->moneda
                ]);
            }

            $saldo = $pagoTotal->minus($transaccion->importe->amount);
            // Log::debug(json_encode([(string)$pagoTotal, (string)$transaccion->importe, (string)$saldo]));
            if ($saldo->isGreaterThan("0") && Arr::get($payload, "registrar_excedentes")) {
                $account = Account::where("cliente_id", $payload["cliente_id"])
                    ->where("moneda", $payload["moneda"])
                    ->first();
                if (!$account) {
                    $account = Account::create(Arr::only($payload, ["cliente_id", "moneda"]) + [
                        "balance" => "0"
                    ]);
                }      
                $pagable = $account;          
                $transaccion->detalles()->create([
                    "moneda" => $pagable->moneda,
                    "importe" => $saldo,
                    "referencia" => "Excedente",
                    "pagable_id" => $pagable->getMorphKey(),
                    "pagable_type" => $pagable->getMorphClass()
                ]);
                do {
                    $account->refresh();
                    $updated = Account::where("id", $account->id)
                        ->where("updated_at", $account->updated_at)
                        ->update([
                            "balance" => (string) $account->balance->amount->plus($saldo)
                        ]);
                } while (!$updated);
            } else if ($saldo->isLessThan("0")) {
                throw new Exception("La suma de los pagos es inferior al importe a pagar.");
            }
            TransaccionRegistrada::dispatch($transaccion);
            return $transaccion;
        });

        return $transaccion;
    }

    function cancel(Request $request, $transaccionId)
    {
        $transaccion = $this->findTransaccion($transaccionId);
        $this->authorize("cancel", [$transaccion]);
        $payload = $request->validate([
            "motivo" => "required|string"
        ]);
        $anulacion = DB::transaction(function() use($transaccion, $payload){
            $updated = false;
            while(!$updated){
                if($transaccion->estado == 2){
                    throw abort(500, "La transacción ya ha sido anulada.");
                }
                $updated = Transaccion::where("id", $transaccion->id)
                    ->where("updated_at", $transaccion->updated_at)
                    ->update([
                        "estado" => 2
                    ]);
                if(!$updated)
                {
                    $transaccion->refresh();
                }
            }
            
            foreach($transaccion->detalles->sortBy(["pagable_type", "pagable_id"]) as $detalle){
                $pagable = $detalle->pagable;
                if($pagable instanceof Reserva){
                    $updated = Reserva::where("id", $pagable->id)
                        ->where("updated_at", $pagable->updated_at)
                        ->update([
                            "saldo" => (string) $pagable->saldo->plus($detalle->importe)->amount
                        ]);
                    if(!$updated)
                    {
                        abort(500, "Optimistic lock: la reserva {$pagable->id} ha sido actualizada desde que inició la operación.");
                    }
                }
                else if($pagable instanceof Venta){
                    $updated = Venta::where("id", $pagable->id)
                        ->where("updated_at", $pagable->updated_at)
                        ->update([
                            "saldo" => (string) $pagable->saldo->plus($detalle->importe)->amount
                        ]);
                    if(!$updated)
                    {
                        abort(500, "Optimistic lock: la venta {$pagable->id} ha sido actualizada desde que inició la operación.");
                    }
                }
                else if($pagable instanceof Cuota){
                    $pago = $pagable->pagos->first(function($pago) use($detalle){
                        return $pago->fecha->isSameAs($detalle->transaccion->fecha)
                            && $detalle->importe->amount->isEqualTo($pago->importe);
                    });
                    $pago->delete();
                    $pagable->load("pagos");
                    $pagable->recalcularSaldo();
                    $updated = Cuota::where("id", $pagable->id)
                        ->where("updated_at", $pagable->updated_at)
                        ->update([
                            "saldo" => (string) $pagable->saldo->amount,
                            "total_pagos" => (string) $pagable->total_pagos->minus($detalle->importe)->amount
                        ]);
                    if(!$updated)
                    {
                        abort(500, "Optimistic lock: la cuota {$pagable->codigo} ha sido actualizada desde que inició la operación.");
                    }
                }
                else if($pagable instanceof Account){
                    $updated = Account::where("id", $pagable->id)
                    ->where("updated_at", $pagable->updated_at)
                    ->update([
                        "balance" => (string) $pagable->balance->minus($detalle->importe)->amount
                    ]);
                    if(!$updated)
                    {
                        abort(500, "Optimistic lock: la cuenta {$pagable->id} ha sido actualizada desde que inició la operación.");
                    }
                }
            }

            return $transaccion->anulacion()->create($payload + [
                "fecha" => Carbon::today()
            ]);
        });

        return $anulacion;
    }

    function findPagable($type, $id)
    {
        if ($type == Reserva::class || $type == Venta::class) {
            return $type::find($id);
        } else if ($type == Cuota::class) {
            return $type::where("codigo", $id)->whereHas("credito", function ($query) {
                $query->where("estado", 1);
            })->first();
        }
    }

    /**
     * @return Transaccion 
     */
    function findTransaccion($id)
    {
        $transaccion = Transaccion::find($id);
        if (!$transaccion) {
            throw new ModelNotFoundException("No existe una transacción con id '$id'");
        }
        return $transaccion;
    }
}
