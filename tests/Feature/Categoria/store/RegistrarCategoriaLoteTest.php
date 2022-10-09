<?php

use App\Models\CategoriaLote;
use App\Models\User;

it('registra una categoria', function () {
    $data = CategoriaLote::factory()->raw();
    $proyectoId = $data["proyecto_id"];
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/categorias", $data);
    $response->assertCreated();
});
