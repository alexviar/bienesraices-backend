<?php

use App\Models\Plano;

test('change estado via accessors', function () {
    $plano = new Plano();
    $plano->estado = 0xFF;
    $plano->is_vigente = false;
    expect($plano->estado)->toBe(0xFE);
    $plano->is_vigente = true;
    expect($plano->estado)->toBe(0xFF);

    $plano->is_locked = false;
    expect($plano->estado)->toBe(0xFD);
    $plano->is_locked = true;
    expect($plano->estado)->toBe(0xFF);

    $plano->estado = 0x00;
    $plano->is_vigente = false;
    expect($plano->estado)->toBe(0x00);
    $plano->is_vigente = true;
    expect($plano->estado)->toBe(0x01);
    
    $plano->is_locked = false;
    expect($plano->estado)->toBe(0x01);
    $plano->is_locked = true;
    expect($plano->estado)->toBe(0x03);
});
