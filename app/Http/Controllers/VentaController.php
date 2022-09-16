<?php

namespace App\Http\Controllers;

use App\Events\VentaCreated;
use App\Http\Reports\Venta\HistorialPagos;
use App\Http\Reports\Venta\PlanPagosPdfReporter;
use App\Models\Credito;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Talonario;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Exception;
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
        $reserva = Reserva::find($request->get("reserva_id"));
        $monedaVenta = Currency::find($request->input("moneda"));
        return [$monedaVenta, $reserva];
    }

    function validateStoreRequest(Request $request, $proyectoId){        
        [$monedaVenta, $reserva] = $this->preprocesStoreRequest($request);
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
            "importe_pendiente" => "prohibited_unless:tipo,2|numeric",
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
            // "credito.cuota_inicial" => "required_with:credito|numeric",
            "credito.tasa_interes" => "required_with:credito|numeric",
            "credito.plazo" => "required_with:credito|numeric|integer",
            "credito.periodo_pago" => "required_with:credito|in:1,2,3,4,6",
            "credito.dia_pago" => "required_with:credito|min:1|max:31"
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
        if($reserva){
            $importe = $payload["tipo"] == 2 ? $reserva->saldo_credito : $reserva->saldo_contado;
            $payload["importe"] = (string) $importe->exchangeTo($payload["moneda"])->round(2)->amount;
            $payload["importe_pendiente"] = (string) $reserva->saldo_contado->minus($importe)->exchangeTo($payload["moneda"])->round(2)->amount;
            $payload["lote_id"] = $reserva->lote->id;
            $payload["cliente_id"] = $reserva->cliente_id;
            $payload["vendedor_id"] = $reserva->vendedor_id;
        }
        $payload["saldo"] = $payload["importe"];
        $payload["proyecto_id"] = $proyecto->id;

        $this->authorize("create", [Venta::class, $payload]);

        $record = DB::transaction(function() use($proyecto, $reserva, $payload, $request){
            /** @var Venta $record */
            $record = Venta::create($payload);

            if($record->tipo == 2) {
                /** @var Credito $credito */

                do{
                    $talonario = Talonario::where("tipo", Credito::class)->latest("id")->first();
                    $siguiente = $talonario->siguiente;
                    $updated = Talonario::where("id", $talonario->id)
                        ->where("updated_at", $talonario->updated_at)
                        ->update([
                            "siguiente" => $siguiente + 1
                        ]);
                }while(!$updated);
                $credito = $record->credito()->create($payload["credito"]+[
                    // "fecha" => $payload["fecha"],
                    "codigo" => $siguiente,
                    "tasa_mora" => $proyecto->tasa_mora,
                    "tasa_interes" => $proyecto->tasa_interes,
                ]);
                $credito->build();
            }

            VentaCreated::dispatch($record, $request->user()->id);

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
