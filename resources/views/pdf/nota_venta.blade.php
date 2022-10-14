<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Nota de venta</title>
    <style>
        @page {
            margin: 20;
            font-size: 12px;
            line-height: 1;
        }
        body {
            margin-bottom: 20;
        }
        * {
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }
    </style>
    <style>
        .center {
            text-align: center;
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
    <div className="mb-2">
        <div style="display:inline-block;width:88px;vertical-align:top">
            <img style="width:100%" src="{{$logo}}" />
        </div>
        <div style="display:inline-block;vertical-align:top;margin-left:10px">
            <h1 style="margin-top:0;line-height:1">NOTA DE VENTA</h1>
            <div><span style="display:inline-block;vertical-align:middle">Importe: </span><div style="padding-left:5px;display:inline-block;vertical-align:middle">{{$importeNumeral}}</div></div>
            <br/>
            <div><span style="display:inline-block;vertical-align:middle">Fecha: </span><div style="padding-left:5px;display:inline-block;vertical-align:middle">{{$fecha}}</div></div>
            <div style="position:absolute;top:0;right:0">Nº {{$numero}}</div>
        </div>
    </div>
    <p style="line-height: 2;text-align:justify">
        Se registró la venta al <b>{{$tipoVenta}}</b> del lote <b>{{$codigoLote}}</b> del proyecto <b>{{$nombreProyecto}}</b> por un valor de 
        <b style="text-transform:uppercase">{{$importeTextual[0]}}</b> con <b style="text-transform:uppercase">{{$importeTextual[0]}}</b> centavos.
        A nombre de <b>{{$nombreCliente}}</b> con <b>{{$tipoDocumento}} {{$documento}}</b>
    </p>
</body>
</html>