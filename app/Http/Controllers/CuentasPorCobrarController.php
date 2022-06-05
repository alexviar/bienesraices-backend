<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\CodigoPago;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CuentasPorCobrarController extends Controller
{

    private function resolvePagoCliente($codigoPago, $fecha){
        //No use whereHas en el modelo Venta porque necesito los datos del cliente
        //y creo que el uso de whereHas implica una subquery la cual podria evitarse
        //Venta::whereHas = 1 subquery
        //$venta->cliente = 1 query extra (ya sea lazy o eager load)
        $query = Venta::with(["cuotas.venta"]);
        if(Str::startsWith($codigoPago, "CLI")){
            $cliente = Cliente::find(Str::substr($codigoPago, 4));
            if(!$cliente) return;
        }
        else{
            $codigo = CodigoPago::where("codigo", $codigoPago)->first();
            if(!$codigo) return;
            $cliente = $codigo->cliente;
            $query->where("proyecto_id", $codigo->proyecto_id);
        }

        $ventas = $query->where("cliente_id", $cliente->id)
            ->where("tipo", 2)
            ->where("estado", 1)
            ->get();

        $now = $fecha ? Carbon::createFromFormat("!Y-m-d", $fecha) : Carbon::now();
        $cuotas = $ventas->reduce(function($carry, $venta) use($now){
            $i = 0;
            do{
                $cuota = $venta->cuotas[$i];
                if($cuota->saldo->amount->isGreaterThan(BigDecimal::zero())) $carry[] = $cuota->toTransactableArray($now);
                $i++;
            }while($now->isAfter($cuota->vencimiento) && $i < $venta->cuotas->count());
            return $carry;
            // $carry = $carry + $venta->getCuotasPendientes();
        }, []);

        return [
            "cliente" => [
                "id" => $cliente->id,
                "nombre_completo" => $cliente->nombreCompleto
            ],
            "cuentas" => $cuotas
        ];
    }

    function index(Request $request) {
        $codigoPago = $request->get("codigo_pago");
        $fecha = $request->get("fecha");

        if($result = $this->resolvePagoCliente($codigoPago, $fecha)){
            return $result;
        }
        abort(404);

        // if(Str::startsWith($codigoPago, "NIT")){
        //     $id = Str::substr($codigoPago, 3);
        //     $cliente = Cliente::where("tipo_documento", 2)->where("numero_documento", $id)->first();

        //     if(!$cliente) throw new NotFoundHttpException();

        //     $ventas = Venta::where("cliente_id", $cliente)->where("estado", 1)->where("tipo", 2)->get();
        //     $cuotasPendientes = $ventas
        //     // ->sortBy(function($venta){
        //     //     return $venta->fecha;
        //     // })
        //     ->reduce(function($carry, $venta) use($now){
        //         $cuotas = $venta->cuotas;
        //         // ->sortBy(function($cuota){
        //         //     return $cuota->vencimiento;
        //         // });

        //         $i = 0;
        //         do{
        //             $cuota = $cuotas[$i];
        //             $i++;
        //             if((new BigDecimal($cuota->saldo))->isGreaterThan(BigDecimal::zero())) $carry[] = [
        //                 "referencia" => $cuota->referencia,//"Pago de la cuota $i del crédito {$cuota->venta->id}",
        //                 "saldo" => $cuota->saldo,
        //                 "saldo_pendiente" => $cuota->saldo_pendiente,
        //                 "multa" => $cuota->multa,
        //                 "transactable_type" => Cuota::class,
        //                 "transactable_id" => $cuota->id
        //             ];
        //         }while($now->isAfter($cuota->vencimiento));
        //         return $carry;
        //     }, []);

        //     return response()->json([
        //         "cliente" => [
        //             "id" => $cliente->id,
        //             "nombre_completo" => $cliente->nombre_completo,
        //         ],
        //         "cuotas" => $cuotasPendientes
        //     ]);
        // }
        // else if(Str::startsWith($codigoPago, "CI")){
        //     $id =  Str::substr($codigoPago, 2);
        //     $cliente = Cliente::where("tipo_documento", 1)->where("numero_documento", $id)->first();

        //     if(!$cliente) throw new NotFoundHttpException();

        //     $cuotasPendientes = Cuota::where("saldo", ">=", "0.1")->whereHas("venta", function($query) use($cliente){
        //         $query->where("cliente_id", $cliente->id);
        //     })->get();
        //     return response()->json([
        //         "cliente" => [
        //             "id" => $cliente->id,
        //             "nombre_completo" => $cliente->nombre_completo,
        //         ],
        //         "cuotas" => $cuotasPendientes
        //     ]);
        // }
        // abort("400");
    }
}
