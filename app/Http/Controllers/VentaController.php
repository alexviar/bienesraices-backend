<?php

namespace App\Http\Controllers;

use App\Http\Reports\Venta\HistorialPagos;
use App\Http\Reports\Venta\PlanPagosPdfReporter;
use App\Models\Credito;
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
use Throwable;

class VentaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
 
    }

    function index(Request $request, $proyectoId)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $data = $this->buildResponse(Venta::with(["cliente", "vendedor", "lote.manzana", "credito"])->where("proyecto_id", $proyectoId), $queryArgs);
        return $data;
    }

    /**
     * @return Venta
     */
    protected function findVenta($proyectoId, $ventaId){
        $venta = Venta::where("proyecto_id", $proyectoId)->where("id", $ventaId)->first();
        if(!$venta) throw new ModelNotFoundException();
        return $venta;
    }

    function preprocesStoreRequest(Request $request){
        $montoAPagar = null;
        try{
            $reserva = Reserva::find($request->get("reserva_id"));
            $monedaPago = Currency::find($request->input("pago.moneda"));
            $monedaVenta = Currency::find($request->input("moneda"));
            $importeVenta = new Money(
                BigDecimal::of($request->get("tipo") == 2 ? 
                    $request->input("credito.cuota_inicial") : 
                    $request->get("importe")
                )->toScale(2, RoundingMode::HALF_UP),
                $monedaVenta
            );
            $importeReserva = $reserva ? $reserva->importe : new Money("0", $importeVenta->currency);

            if($importeVenta->currency->code !== $importeReserva->currency->code){
                $importeVenta = $importeVenta->exchangeTo($monedaPago)->round(2);
                $importeReserva = $importeReserva->exchangeTo($monedaPago, [
                    "exchangeMode" => Money::BUY
                ])->round(2);
            }
            $montoAPagar = $importeVenta->minus($importeReserva)->exchangeTo($monedaPago)->round(2);
        }catch(Throwable $e){ }
        return [$montoAPagar, $monedaVenta, $monedaPago, $reserva];
    }

    function validateStoreRequest(Request $request, $proyectoId){        
        [$montoAPagar, $monedaVenta, $monedaPago, $reserva] = $this->preprocesStoreRequest($request);
        $payload = $request->validate([
            "tipo" => "required|in:1,2",
            "fecha" => "required|date|before_or_equal:".now()->format("Y-m-d"),
            "moneda" => ["required", function($attribute, $value, $fail) use($monedaVenta){
                if(!$monedaVenta) $fail("Moneda invalida.");
            }],
            "lote_id" => ["required_without:reserva_id", function ($attribute, $value, $fail) use($request, $reserva, $proyectoId){
                $lote = $reserva ? $reserva->lote : Lote::find($value);
                if(!$lote || $lote->proyecto->id != $proyectoId){
                    $fail('Lote inválido.');
                }
                else if($lote->estado["code"] !== 1){
                    $cliente_id = $reserva ? $reserva->cliente_id : $request->input("cliente_id");
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
            "reserva_id" => ["nullable", function ($attribute, $value, $fail) use($reserva){
                if(!$reserva){
                    $fail("Reserva invalida.");
                }
                else if($reserva->estado !== 1){
                    $fail("La reserva ha sido anulada o se ha concretado la venta.");
                }
                //No se realiza una restriccion basada en el vencimiento de la reserva
                //para dejar al criterio del operador si efectua o no la venta en casos excepcionales
            }],

            "credito" => "required_if:tipo,2",
            "credito.cuota_inicial" => "required_with:credito|numeric",
            // "credito.tasa_interes" => "required_with:credito|numeric",
            "credito.plazo" => "required_with:credito|numeric|integer",
            "credito.periodo_pago" => "required_with:credito|in:1,2,3,4,6",
            "credito.dia_pago" => "required_with:credito|min:1|max:31",

            "pago.moneda" => ["required", function($attribute, $value, $fail) use($monedaPago){
                if(!$monedaPago) $fail("Moneda de pago invalida.");
            }],
            "pago.monto" => ["required", "numeric", function ($attribute, $value, $fail) use($montoAPagar){
                if($montoAPagar && $montoAPagar->amount->isGreaterThan($value)){
                    // $fail("El pago ({$pago->toScale(2)} {$monedaPago->code}) es menor al monto a pagar ({$montoAPagar->toScale(2)} {$monedaPago->code}).");
                    $fail("El pago es menor al monto a pagar.");
                }
            }],
            "pago.comprobante" => "required|image",
            "pago.numero_transaccion" => "required|integer"
        ], [
            "fecha.before_or_equal" => "El campo ':attribute' no puede ser posterior a la fecha actual."
        ]);

        return [$payload, $reserva];
    }

    function store(Request $request, $proyectoId){  
        $proyecto = Proyecto::find($proyectoId);
        if(!$proyecto){
            throw new ModelNotFoundException("El proyecto no existe");
        }    
        [$payload, $reserva] = $this->validateStoreRequest($request, $proyectoId);
        if($reserva && $reserva->estado == 1){
            $payload["lote_id"] = $reserva->lote->id;
            $payload["cliente_id"] = $reserva->cliente_id;
            $payload["vendedor_id"] = $reserva->vendedor_id;
        }

        $record = DB::transaction(function() use($proyecto, $reserva, $payload){
            /** @var Venta $record */
            $record = Venta::create([
                "proyecto_id" => $proyecto->id
            ]+$payload);

            if($record->tipo == 2) {
                /** @var Credito $credito */
                $credito = $record->credito()->create($payload["credito"]+[
                    "tasa_mora" => $proyecto->tasa_mora,
                    "tasa_interes" => $proyecto->tasa_interes,
                ]);
                $credito->build();
            }

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
            
            $importeReserva = $reserva ? $reserva->importe->exchangeTo($record->getCurrency(), [
                "exchangeMode" => Money::BUY
            ])->round() : "0";
            $detailModel = new DetalleTransaccion();
            $detailModel->moneda = $record->getCurrency()->code;
            if($record->tipo == 1){
                $detailModel->referencia = $record->getReferencia();
                $detailModel->importe = $record->importe->minus($importeReserva)->amount;
                $transaccion->detalles()->save($detailModel);
                $detailModel->ventas()->save($record);
            }
            else {
                $credito = $record->credito;
                $detailModel->referencia = $credito->getReferencia();
                $detailModel->importe = $credito->cuota_inicial->minus($importeReserva)->amount;
                $transaccion->detalles()->save($detailModel);
                $detailModel->creditos()->save($credito);
            }

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
