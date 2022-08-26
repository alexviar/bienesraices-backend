<?php

namespace App\Http\Reports\Venta;

use App\Models\Credito;
use App\Models\DetalleTransaccion;

class HistorialPagos {

    function generate(Credito $credito){
        $image = public_path("logo192.png");
        $mime = getimagesize($image)["mime"];
        $data = file_get_contents($image);
        $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($data);

        $venta = $credito->creditable;

        $pagos = DetalleTransaccion::whereHas("reservas", function($query) use($venta){
            $query->where("id", $venta->reserva_id);
        })->union(DetalleTransaccion::whereHas("creditos", function($query) use($credito){
            $query->where("id", $credito->id);
        }))->union(DetalleTransaccion::whereHas("cuotas", function($query) use($credito){
            $query->where("credito_id", $credito->id);
        }))->get();
        
        $zero = new \App\Models\ValueObjects\Money("0", $venta->currency);
        $totalPagos = $pagos->reduce(function($carry, $pago){
            return $carry->add($pago->importe->exchangeTo($carry->currency, ["exchangeMode"=>"buy"]));
        }, $zero)->round();
        
        $today = \Illuminate\Support\Carbon::today();
        $saldoMora = $zero;
        $totalPagado = $zero;
        $i = 0;
        while($credito->cuotas[$i]->vencimiento->isBefore($today) && $i < $credito->cuotas->count()){
            $saldoMora = $saldoMora->plus($credito->cuotas[$i]->calcularPago($today)->toScale(2, \Brick\Math\RoundingMode::HALF_UP));
            $totalPagado = $totalPagado->plus($credito->cuotas[$i]->importe->minus($credito->cuotas[$i]->saldo));
            $i++;
        }
        $totalPagado = $totalPagado->plus($credito->cuotas[$i]->importe->minus($credito->cuotas[$i]->saldo));
        $totalMultas = $totalPagos->minus($credito->cuota_inicial)->minus($totalPagado);
        $saldoPendiente = $credito->cuotas[$i]->saldo->plus($venta->credito->cuotas[$i]->saldo_capital)->plus($saldoMora);

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