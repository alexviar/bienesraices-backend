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
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th></th>
                @foreach ($datasets as $dataset)
                    <th>{$dataset->label}</th>
                @endforeach
            </tr>
        </thead>
    </table>
</body>