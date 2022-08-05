<?php

use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

test('example', function () {
    Venta::factory(10)->credito()->create();



});
