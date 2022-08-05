<?php

namespace App\Http\Reports\Venta;

use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Reserva;
use App\Models\Venta;

class HistorialPagos {

    function generate(Venta $venta){
        $image = public_path("logo192.png");
        $mime = getimagesize($image)["mime"];
        $data = file_get_contents($image);
        $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($data);

        $pagos = DetalleTransaccion::whereHas("reservas", function($query) use($venta){
            $query->where("id", $venta->reserva_id);
        })->union(DetalleTransaccion::whereHas("creditos", function($query) use($venta){
            $query->where("id", $venta->credito->id);
        }))->union(DetalleTransaccion::whereHas("cuotas", function($query) use($venta){
            $query->where("credito_id", $venta->credito->id);
        }))->get();
        
        $zero = new \App\Models\ValueObjects\Money("0", $venta->currency);
        $totalPagos = $pagos->reduce(function($carry, $pago){
            return $carry->add($pago->importe->exchangeTo($carry->currency, ["exchangeMode"=>"buy"]));
        }, $zero)->round();
        
        $today = \Illuminate\Support\Carbon::today();
        $saldoMora = $zero;
        $totalPagado = $zero;
        $i = 0;
        while($venta->credito->cuotas[$i]->vencimiento->isBefore($today) && $i < $venta->credito->cuotas->count()){
            $saldoMora = $saldoMora->plus($venta->credito->cuotas[$i]->calcularPago($today)->toScale(2, \Brick\Math\RoundingMode::HALF_UP));
            $totalPagado = $totalPagado->plus($venta->credito->cuotas[$i]->importe->minus($venta->credito->cuotas[$i]->saldo));
            $i++;
        }
        $totalPagado = $totalPagado->plus($venta->credito->cuotas[$i]->importe->minus($venta->credito->cuotas[$i]->saldo));
        $totalMultas = $totalPagos->minus($venta->credito->cuota_inicial)->minus($totalPagado);
        $saldoPendiente = $venta->credito->cuotas[$i]->saldo->plus($venta->credito->cuotas[$i]->saldo_capital)->plus($saldoMora);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView("pdf.historial_pagos", [
            "img" => $dataUri,
            "venta" => $venta,
            "pagos" => $pagos,
            "totalPagos" => $totalPagos,
            "totalMultas" => $totalMultas,
            "saldoMora" => $saldoMora,
            "saldoPendiente" => $saldoPendiente
        ])->setPaper([0, 0, 72*8.5, 72*13]);
    }
}