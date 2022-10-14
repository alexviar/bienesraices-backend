<?php

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Plano;
use App\Models\Venta;

test('el lote pertenece a un plano que no esta vigente', function () {
    $plano = new Plano([
        "estado" => 0xFE
    ]);

    $manzana = new Manzana();
    $manzana->setRelation("plano", $plano);
    $lote = new Lote();
    $lote->setRelation("manzana", $manzana);
    $venta = new Venta();
    $venta->setRelation("lote", $lote);
    expect($venta->observaciones)->not->toBeEmpty();
});
