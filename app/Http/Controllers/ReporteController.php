<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Reserva;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReporteController extends Controller
{
    function reporte_pagos(Request $request)
    {
        $today = Carbon::today();
        $start = Carbon::createFromFormat("Y-m-d", $request->desde)->startOfDay();
        $end =  Carbon::createFromFormat("Y-m-d", $request->hasta)->endOfDay();
        if ($end->diffInDays($start) < 7) {
            $groupBy = "day";
            $labelFormat = "ddd DD";
        } else if($end->month == $start->month){
            $groupBy = "day";
            $labelFormat = "Do";
        } else if($end->year == $start->year){
            $groupBy = "month";
            $labelFormat = "MMMM";
        } else {
            $groupBy = "year";
            $labelFormat = "YYYY";
        }
        $end = $end->isAfter($today) ? $today->endOfDay() : $end;

        $description = "{$start->isoFormat('L')} - {$end->isoFormat('L')}";

        $detalles = DetalleTransaccion::whereHas("transaccion", function ($query) use ($start, $end) {
            $query->where("fecha", ">=", $start)
                ->where("fecha", "<=", $end)
                ->where("estado", 1);
        })->whereIn("pagable_type", [Reserva::class, Venta::class, Cuota::class])->with("transaccion")->get();

        $labels = [];
        $current = $start->copy();
        while (!$end->isBefore($current)) {
            // $label = $current->isoFormat($groupBy == "day" ? "ddd DD" : ($periodo == 2 ?  "Do" : ($periodo == 4  ? "MMMM" : "YYYY")));
            $label = $current->isoFormat($labelFormat);
            $labels[] = \Illuminate\Support\Str::upper($label[0]) . \Illuminate\Support\Str::substr($label, 1);
            $current = $start->copy()->add(count($labels), $groupBy, false);
        }

        $datasets = $detalles->reduce(function ($carry, $item) use ($start, $labelFormat) {
            $i = $item->pagable_type == Reserva::class ? 0 : ($item->pagable_type == Venta::class ? ($item->pagable->tipo == 1 ? 1 : 2) : 3
            );
            $j = $labelFormat == "ddd DD" ?
                (7 + $item->transaccion->fecha->dayOfWeekIso - $start->dayOfWeekIso) % 7 :
                ($labelFormat == "Do" ?
                    $item->transaccion->fecha->day - $start->day :
                    ($labelFormat == "MMMM" ?
                        $item->transaccion->fecha->month - $start->month :
                        $item->transaccion->fecha->year - $start->year));
            $importe = $item->importe->exchangeTo("BOB", [
                "date" => $item->transaccion->fecha
            ])->round(2);
            $carry[$i]["data"][$j] = (string)$importe->amount->plus($carry[$i]["data"][$j]);
            $carry[$i]["total"] = (string)$importe->amount->plus($carry[$i]["total"]);
            return $carry;
        }, array_map(function ($label) use ($labels) {
            return [
                "label" => $label,
                "data" => array_map(function () {
                    return "0";
                }, $labels),
                "total" => "0"
            ];
        }, ["Reservas", "Ventas al contado", "Ventas al credito", "Pago de cuotas"]));
        if ($request->format == "xlsx") {
            return response()
                ->view('reportes.reporte_pagos', [
                    "labels" => $labels,
                    "datasets" => $datasets,
                ])
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="reporte.xlsx"');
        } else {
            return [
                "description" => $description,
                "labels" => $labels,
                "datasets" => $datasets,
                "download_link" => route("reportes.pagos") . "?" . http_build_query($request->all())
                // "download_link" => "data:text/csv;charset=utf-8,".array_map(function($label, $i) use($datasets){
                //     return "$label,{$datasets[0]['data'][]}"
                // }, $labels)
            ];
        }
    }
}
