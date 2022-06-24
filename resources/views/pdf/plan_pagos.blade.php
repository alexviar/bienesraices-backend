<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Plan de pagos</title>
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
            font-size: 0.8333rem;
            border: 1px solid;
            padding: 0.2rem 0.5rem;
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

    <div class="title center bg-primary">PLAN DE PAGOS</div>
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
                                                <th scope="row" class="text-right"><b>Cuota:</b></th>
                                                <td class="text-left"> {{$venta->cuotas[0]->importe}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Nº de cuotas:</b></th>
                                                <td class="text-left"> {{$venta->cuotas->count()}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Total intereses:</b></th>
                                                <td class="text-left"> {{$venta->total_intereses}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="text-right"><b>Total crédito:</b></th>
                                                <td class="text-left"> {{$venta->total_credito}}</td>
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
            <th style="width:5%">#</th>
            <th style="width:19%">Fecha vencimiento</th>
            <th style="width:19%">Pago</th>
            <th style="width:19%">Interes</th>
            <th style="width:19%">Amortizacion</th>
            <th style="width:19%">Saldo</th>
        </thead>
        <tbody>
            <tr>
                <th scope="row">0</th>
                <td>{{$venta->fecha->format("d/m/Y")}}</td>
                <td class="text-right">{{$venta->cuota_inicial}}</td>
                <td class="text-right">- {{$venta->moneda}}</td>
                <td class="text-right">{{$venta->cuota_inicial}}</td>
                @php
                $saldo = $venta->importe->minus($venta->cuota_inicial);
                @endphp
                <td class="text-right">{{$saldo}}</td>
            </tr>
            @for ($i = 0; $i < $venta->cuotas->count(); $i++)
            @php
                $importe = $venta->cuotas[$i]->importe;
                $amortizacion = $saldo->minus($venta->cuotas[$i]->saldo_capital);
                $interes = $importe->minus($amortizacion);
                $saldo = $venta->cuotas[$i]->saldo_capital;
            @endphp
            <tr>
                <th scope="row">{{$i+1}}</th>
                <td>{{$venta->cuotas[$i]->vencimiento->format("d/m/Y")}}</td>
                <td class="text-right">{{$importe}}</td>
                <td class="text-right">{{$interes}}</td>
                <td class="text-right">{{$amortizacion}}</td>
                <td class="text-right">{{$saldo}}</td>
            </tr>
            @endfor
        </tbody>
    </table>
    <!-- @php
        $i = 0
    @endphp
    @while(true)
    <div class="page_break"></div>
    <table class="tabla-amortizacion">
        <thead>
            <th style="width:5%">#</th>
            <th style="width:19%">Fecha vencimiento</th>
            <th style="width:19%">Pago</th>
            <th style="width:19%">Interes</th>
            <th style="width:19%">Amortizacion</th>
            <th style="width:19%">Saldo</th>
        </thead>
        <tbody>
            @while($i < $venta->cuotas->count())
            @php
                $importe = $venta->cuotas[$i]->importe;
                $amortizacion = $saldo->minus($venta->cuotas[$i]->saldo_capital);
                $interes = $importe->minus($amortizacion);
                $saldo = $venta->cuotas[$i]->saldo_capital;
            @endphp
            <tr>
                <th scope="row">{{$i+1}}</th>
                <td>{{$venta->cuotas[$i]->vencimiento->format("d/m/Y")}}</td>
                <td class="text-right">{{$importe}}</td>
                <td class="text-right">{{$interes}}</td>
                <td class="text-right">{{$amortizacion}}</td>
                <td class="text-right">{{$saldo}}</td>
            </tr>     
            @break(++$i % 48 == 0)
            @endwhile
            @break($i >= $venta->cuotas->count())
        </tbody>
    </table>
    @endwhile -->
    <footer>Deposite sus cuotas en la cuenta Nº XXXXXXXXXXX del Banco Fassil, a nombre de XXXX XXXX XXXX. No olvide incluir su codigo de pago en la referencia o glosa del deposito.</footer>
</body>
</html>