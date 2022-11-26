<?php

namespace App\Http\Reports\Venta;

use App\Models\Credito;
use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Reserva;
use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class HistorialPagos {

    function generate(Credito $credito){
        $image = public_path("logo192.png");
        $mime = getimagesize($image)["mime"];
        $data = file_get_contents($image);
        $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($data);

        $venta = $credito->creditable;

        /** @var Builder $query */
        $query = DetalleTransaccion::query();
        $reserva = $venta->reserva;
        if($reserva) {
            $query->whereMorphedTo("pagable", $reserva);
        }
        $query->orWhereMorphedTo("pagable", $venta);
        $query->orWhereMorphedTo("pagable", $credito->cuotas);

        $pagos = $query->get();
        
        $zero = new \App\Models\ValueObjects\Money("0", $venta->moneda);
        $totalPagos = $pagos->reduce(function($carry, $pago){
            return $carry->add($pago->importe->exchangeTo($carry->currency, [
                "exchangeMode" => Money::BUY
            ]));
        }, $zero)->round(2);
        
        $saldoMora = $zero;
        $totalMultas = $zero;
        $cuota = $credito->cuotas->first();
        while($cuota && $cuota->vencida){
            $saldoMora = $saldoMora->plus($cuota->total->round(2));
            $totalMultas = $totalMultas->plus($cuota->total_multas->round(2));
            $cuota = $cuota->siguiente;
        }
        $saldoPendiente = $saldoMora;
        if($cuota){
            $saldoPendiente = $saldoPendiente->plus($cuota->saldo->plus($cuota->saldo_capital));
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView("pdf.historial_pagos", [
            "img" => $dataUri,
            "venta" => $venta,
            "credito" => $credito,
            "pagos" => $pagos,
            "totalPagos" => $totalPagos,
            "totalMultas" => $totalMultas,
            "saldoMora" => $saldoMora,
            "saldoPendiente" => $saldoPendiente
        ])->setPaper([0, 0, 72*8.5, 72*13]);
    }
}