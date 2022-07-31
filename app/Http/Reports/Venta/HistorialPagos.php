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

        return \Barryvdh\DomPDF\Facade\Pdf::loadView("pdf.historial_pagos", [
            "img" => $dataUri,
            "venta" => $venta,
            "pagos" => DetalleTransaccion::with("transaccion")->whereHasMorph("transactable", [Venta::class, Cuota::class, Reserva::class], function($query, $type) use($venta){
                if($type === Venta::class){
                    $query->where("id", $venta->id);
                }
                else if($type === Cuota::class){
                    $query->where("venta_id", $venta->id);
                }
                else {
                    if($venta->reserva_id){
                        $query->where("id", $venta->reserva_id);
                    }
                }
            })->get()
        ])->setPaper([0, 0, 72*8.5, 72*13]);
    }
}