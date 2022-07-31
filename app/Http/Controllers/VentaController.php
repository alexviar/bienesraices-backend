<?php

namespace App\Http\Controllers;

use App\Http\Reports\Venta\HistorialPagos;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
 
    }

    function index(Request $request, $proyectoId)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $data = $this->buildResponse(Venta::with(["cliente", "vendedor", "lote.manzana"])->where("proyecto_id", $proyectoId), $queryArgs);
        // $data["records"]->each->setVisible([
        //     "id"
        // ]);
        return $data;
    }

    function print_plan_pagos(Request $request, $proyectoId, $ventaId){
        $venta = Venta::where("proyecto_id", $proyectoId)->where("id", $ventaId)->first();
        if(!$venta) throw new ModelNotFoundException();
        $image = public_path("logo192.png");
        $mime = getimagesize($image)["mime"];
        $data = file_get_contents($image);
        $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($data);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView("pdf.plan_pagos", [
            "img" => $dataUri,
            "venta" => $venta
        ])->setPaper([0, 0, 72*8.5, 72*13])->stream();
    }

    /**
     * @return Venta
     */
    protected function findVenta($proyectoId, $ventaId){
        $venta = Venta::where("proyecto_id", $proyectoId)->where("id", $ventaId)->first();
        if(!$venta) throw new ModelNotFoundException();
        return $venta;
    }

    function print_historial_pagos(Request $request, HistorialPagos $report, $proyectoId, $ventaId){
        $venta = $this->findVenta($proyectoId, $ventaId);
        return $report->generate($venta)->stream("historial_pagos.pdf");
    }

    function store(Request $request, $proyectoId){

        $payload = $request->validate([
            "tipo" => "required|in:1,2",
            "fecha" => "required|date|before_or_equal:".now()->format("Y-m-d"),
            "moneda" => "required|exists:currencies,code",
            "lote_id" => ["required_without:reserva_id", function ($attribute, $value, $fail) use($request, $proyectoId){
                $reserva = Reserva::find($request["reserva_id"]);
                $lote = $reserva ? $reserva->lote : Lote::find($value);
                if(!$lote || $lote->proyecto->id != $proyectoId){
                    $fail('Lote inválido.');
                }
                else if($lote->estado["code"] !== 1){
                    $cliente_id = $request->input("reserva_id") ? Reserva::find($request->input("reserva_id"))->cliente_id : $request->input("cliente_id");
                    if($lote->estado["code"] === 3){
                        if($lote->reserva->cliente_id != $cliente_id){
                            $fail("El lote ha sido reservado por otro cliente.");
                        }
                    }
                    else{
                        $fail('El lote no esta disponible.');
                    }
                }
            }],
            "importe" => "required|numeric",
            "cliente_id" => "required_without:reserva_id|nullable|exists:clientes,id",
            "vendedor_id" => "required_without:reserva_id|nullable||exists:vendedores,id",
            "reserva_id" => ["nullable", function ($attribute, $value, $fail){
                $reserva = Reserva::find($value);
                if(!$reserva){
                    $fail("Reserva invalida.");
                }
                else if($reserva->estado !== 1){
                    $fail("La reserva ha sido anulada o se ha concretado la venta.");
                }
                //No se realiza una restriccion basada en el vencimiento de la reserva
                //para dejar al criterio del operador si efectua o no la venta en casos excepcionales
            }],

            "cuota_inicial" => "required_if:tipo,2|numeric",
            "tasa_interes" => "required_if:tipo,2|numeric",
            "plazo" => "required_if:tipo,2|numeric|integer",
            "periodo_pago" => "required_if:tipo,2|in:1,2,3,4,6",
            "dia_pago" => "required_if:tipo,2|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31",

            "pago.moneda" => "required|exists:currencies,code",
            "pago.monto" => ["required", "numeric", function ($attribute, $value, $fail) use($request){
                $reserva = Reserva::find($request->get("reserva_id"));
                $monedaPago = Currency::find($request->input("pago.moneda"));
                $pago = BigDecimal::of($value)->toScale(2, RoundingMode::HALF_UP);
                $monedaVenta = Currency::find($request->input("moneda"));
                $importeVenta = new Money(
                    BigDecimal::of($request->get("tipo") == 2 ? $request->get("cuota_inicial") : BigDecimal::of($request->get("importe")))->toScale(2, RoundingMode::HALF_UP),
                    $monedaVenta
                );
                $importeReserva = $reserva ? $reserva->importe : new Money("0", $importeVenta->currency);

                if($importeVenta->currency->code !== $importeReserva->currency->code){
                    $importeVenta = $importeVenta->exchangeTo($monedaPago)->round(2);
                    $importeReserva = $importeReserva->exchangeTo($monedaPago, [
                        "exchangeMode" => Money::BUY
                    ])->round(2);
                }
                $montoAPagar = $importeVenta->minus($importeReserva)->exchangeTo($monedaPago)->round(2)->amount;
                if($pago->isLessThan($montoAPagar)){

                    // $fail("El pago ({$pago->toScale(2)} {$monedaPago->code}) es menor al monto a pagar ({$montoAPagar->toScale(2)} {$monedaPago->code}).");
                    $fail("El pago es menor al monto a pagar.");
                }
            }],
            "pago.comprobante" => "required|image",
            "pago.numero_transaccion" => "required|integer"
        ], [
            "fecha.before_or_equal" => "El campo ':attribute' no puede ser posterior a la fecha actual."
        ]);
 
        $reserva = isset($payload["reserva_id"]) ? Reserva::find($payload["reserva_id"]) : null;
        if($reserva && $reserva->estado == 1){
            $payload["lote_id"] = $reserva->lote->id;
            $payload["cliente_id"] = $reserva->cliente_id;
            $payload["vendedor_id"] = $reserva->vendedor_id;
        }
        $proyecto = Proyecto::find($proyectoId);

        $record = DB::transaction(function() use($proyecto, $reserva, $payload){
            /** @var Venta $record */
            $record = Venta::create([
                "proyecto_id" => $proyecto->id,
                "tasa_mora" => $proyecto->tasa_mora
            ]+$payload);

            if($record->tipo == 2) $record->crearPlanPago();

            //Aqui idealmente deberiamos despachar un evento para desencadenar otras acciones
            //pero por ahora esas acciones vamos a realizarlas aqui directamente

            //Registrar la transacción
            $transaccion = Transaccion::create([
                "fecha" => $record->fecha,
                "moneda" => Arr::get($payload,"pago.moneda"),
                "importe" => Arr::get($payload, "pago.monto"),
                "numero_transaccion" => Arr::get($payload, "pago.numero_transaccion"),
                "comprobante" => Arr::get($payload, "pago.comprobante")->store("comprobantes"),
                "forma_pago" => 2,
            ]);
            $detailModel = new DetalleTransaccion();
            $detailModel->referencia = $record->getReferencia();
            $detailModel->moneda = $record->getCurrency()->code;
            $detailModel->importe = (new Money(
                ($record->tipo == 1 ? 
                    $record->importe :
                    $record->cuota_inicial
                )->minus($reserva ? 
                    $reserva->importe->exchangeTo($record->getCurrency(), ["exchangeMode"=>Money::BUY]) :
                    "0"
                )->amount, $record->getCurrency()))->amount->toScale(2, RoundingMode::HALF_UP);
            $detailModel->transactable()->associate($record);

            $transaccion->detalles()->save($detailModel);

            //Actualizar el estado de la reserva, si existe
            if($reserva){
                $reserva->estado = 3;
                $reserva->save();
            }

            //Actualizar el estado del lote


            return $record;
        });

        $record->loadMissing(["cliente", "vendedor", "lote.manzana"]);
        return $record;
    }
}
