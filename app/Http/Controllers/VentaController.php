<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Transaccion;
use App\Models\Vendedor;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
 
    }

    function index(Request $request, $proyectoId)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Venta::with(["cliente", "vendedor", "lote.manzana"])->where("proyecto_id", $proyectoId), $queryArgs);
    }

    function store(Request $request, $proyectoId){

        $payload = $request->validate([
            "tipo" => "required|in:1,2",
            "fecha" => "required|date",
            "moneda" => "required|exists:currencies,code",
            "lote_id" => ["required_without:reserva_id", function ($attribute, $value, $fail) use($request, $proyectoId){
                $reserva = Reserva::find($request["reserva_id"]);
                $lote = $reserva ? $reserva->lote : Lote::find($value);
                if(!$lote || $lote->proyecto->id != $proyectoId){
                    $fail('Lote inválido.');
                }
                else if($lote->estado !== "Disponible"){
                    $cliente_id = $request->input("reserva_id") ? Reserva::find($request->input("reserva_id"))->cliente_id : $request->input("cliente_id");
                    if($lote->estado === "Reservado"){
                        if($lote->reserva->cliente_id != $cliente_id){
                            $fail("El lote ha sido reservado por otro cliente.");
                        }
                    }
                    else{
                        $fail('El lote no esta disponible.');
                    }
                }
            }],
            "precio" => "required|numeric",
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
            var_dump($reserva?->importe->exchangeTo($record->currency)->round()->toArray());
            $importe = (string) ($record->tipo == 1 ? $record->precio : $record->cuota_inicial)->minus($reserva ? $reserva->importe->exchangeTo($record->currency)->round() : "0")->amount;
            $transaccion = Transaccion::create([
                "fecha" => $record->fecha,
                "moneda" => $record->moneda,
                "importe" => $importe,
                "forma_pago" => 2,
            ]);
            $detailModel = new DetalleTransaccion();
            $detailModel->referencia = $record->getReferencia();
            $detailModel->moneda = $record->getCurrency()->code;
            $detailModel->importe = $importe;
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
