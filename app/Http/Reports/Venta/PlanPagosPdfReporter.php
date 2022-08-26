<?php

namespace App\Http\Reports\Venta;

use App\Models\Credito;

class PlanPagosPdfReporter {

    function generate(Credito $credito){
        $image = public_path("logo192.png");
        $mime = getimagesize($image)["mime"];
        $data = file_get_contents($image);
        $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($data);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView("pdf.plan_pagos", [
            "img" => $dataUri,
            "venta" => $credito->creditable,
            "credito" => $credito
        ])->setPaper([0, 0, 72*8.5, 72*13]);
    }
}