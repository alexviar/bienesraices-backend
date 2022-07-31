<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Historial de pagos</title>
    <style>
        @page {
            margin: 20;
            font-size: 12px
        }
        body {
            margin-bottom: 20;
        }
        * {
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }
        footer { position: fixed; bottom: 0px; left: 0px; right: 0px; text-align: center; font-size: 0.75rem; color: #999 }
        ul {
            /* margin-top: 5px;
            margin-bottom: 5px */
            margin: 0
        }
        .dashed {
            border-bottom: 1px dashed
        }
        .overlined {
            border-top: 1px dashed
        }
        .services {
            display: table-cell;
            width:33.333%;
            margin-right: -2px;
            margin-left: -2px;
            vertical-align: top
        }
    </style>
    <style>
        table {
            width: 100%;
        }
        .tabla-amortizacion th, .tabla-amortizacion td {
            border: 1px solid;
            padding: 0.25rem 0.5rem;
            margin-bottom: 100px;
        }

        table,
        th,
        td {
          /* position: relative;
          border: 1px dashed black; */
          border-collapse: collapse;
          padding: 0
        }
        .center {
            text-align: center;
        }
        .title {
            text-transform: uppercase;
            padding: 10px;
            color: #fff;
            font-size: 1.5rem;
        }
        .mb-2 {
            margin-bottom: 0.5rem;
        }
        .pl-2 {
            padding-left: 0.5rem;
        }
        .bg-primary {
            background-color: #2f3d07;
        }
        .text-white {
            color: #fff
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
      </style>
</head>
<body>
    @php
        $zero = new \App\Models\ValueObjects\Money("0", $venta->currency);
        $totalPagos = $pagos->reduce(function($carry, $pago){
            return $carry->add($pago->importe);
        }, $zero);
        
        $today = \Illuminate\Support\Carbon::today();
        $saldoMora = $zero;
        $totalPagado = $zero;
        $i = 0;
        while($venta->cuotas[$i]->vencimiento->isBefore($today) && $i < $venta->cuotas->count()){
            $saldoMora = $saldoMora->plus($venta->cuotas[$i]->calcularPago($today)->toScale(2, \Brick\Math\RoundingMode::HALF_UP));
            $totalPagado = $totalPagado->plus($venta->cuotas[$i]->importe->minus($venta->cuotas[$i]->saldo));
            $i++;
        }
        $totalPagado = $totalPagado->plus($venta->cuotas[$i]->importe->minus($venta->cuotas[$i]->saldo));
        $totalMultas = $totalPagos->minus($venta->cuota_inicial)->minus($totalPagado);
        $saldoPendiente = $venta->cuotas[$i]->saldo->plus($venta->cuotas[$i]->saldo_capital)->plus($saldoMora);
    @endphp

    <div class="title center bg-primary">HISTORIAL DE PAGOS</div>
    <br>
    <table style="width:100%">
        <tbody style="vertical-align:top">
            <tr>
            <td style="width:152px;">
                    <img style="width:100%" src="{{$img}}" />
                    <!-- <div class="center"><b>Nº</b> {{$venta->id}}</div> -->
            </td>
            <td class="pl-2">
                <div style="margin-left:-0.5rem;margin-right:-0.5rem;margin-top:-0.5rem">
                    <table style="border-spacing:0.5rem;border-collapse:separate;">
                        <tbody>
                            <tr>
                                <td><b>Proyecto: </b>{{$venta->proyecto->nombre}}</td>
                                <td><b>Mz: </b>{{$venta->manzana->numero}}</div>
                                <td><b>Lote: </b>{{$venta->lote->numero}}</div>
                            </tr>
                            <tr>
                                <td><b>Cliente: </b>{{$venta->cliente->nombre_completo}}</td>
                                <!-- <td><b>Código de pago: </b>{{--$venta->cliente->getCodigoPago($venta->proyecto_id)--}}</div> -->
                                <td><b>Código de pago: </b>{{$venta->cliente->codigo_pago}}</div>
                            </tr>
                        </tbody>
                    </table>
                    <table style="table-layout:fixed">
                        <tbody>
                            <tr>
                                <td>
                                    <table style="border-spacing:0.5rem;border-collapse:separate;table-layout:fixed">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th colspan="2">Parametros del crédito</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Plazo del crédito:</b></th>
                                                <td class="text-left"> {{$venta->plazo}} meses</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Periodo de pago:</b></th>
                                                <td class="text-left"> {{$venta->periodo_pago_text}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Tasa de interés anual:</b></th>
                                                <td class="text-left"> {{\Brick\Math\BigDecimal::of("100")->multipliedBy($venta->tasa_interes)->toScale(2)}} %</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Importe del terreno:</b></th>
                                                <td class="text-left"> {{$venta->importe}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>
                                    <table style="border-spacing:0.5rem;border-collapse:separate;table-layout:fixed">
                                        <thead>
                                            <tr class="bg-primary text-white">
                                                <th colspan="2">Resumen del crédito</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Total pagos:</b></th>
                                                <td class="text-left"> {{$totalPagos}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Total multas:</b></th>
                                                <td class="text-left"> {{$totalMultas}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Saldo en mora:</b></th>
                                                <td class="text-left"> {{$saldoMora}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Saldo pendiente:</b></th>
                                                <td class="text-left"> {{$saldoPendiente}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
            </tr>
        </tbody>
    </table>    
    <br>
    <table class="tabla-amortizacion">
        <thead>
            <th style="width:auto">#</th>
            <th style="width:auto;white-space:nowrap">Fecha de pago</th>
            <th style="width:100%">Referencia</th>
            <th style="width:auto">Monto</th>
        </thead>
        <tbody>
            @for ($i = 0; $i < $pagos->count(); $i++)
            <tr>
                <th scope="row">{{$i+1}}</th>
                <td>{{$pagos[$i]->transaccion->fecha->format("d/m/Y")}}</td>
                <td class="text-left">{{$pagos[$i]->referencia}}</td>
                <td class="text-right" style="white-space:nowrap">{{$pagos[$i]->importe}}</td>
            </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>