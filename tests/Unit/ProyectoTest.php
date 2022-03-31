<?php

namespace Tests\Unit;

use App\Models\Proyecto;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ProyectoTest extends TestCase
{
    public function test_to_array()
    {
        $proyecto = Proyecto::factory()->create();
        $array = Arr::dot($proyecto->toArray());

        $this->assertArrayHasKey("id", $array);
        $this->assertArrayHasKey("nombre", $array);
        $this->assertArrayHasKey("socio", $array);
        $this->assertArrayHasKey("ubicacion.latitud", $array);
        $this->assertArrayHasKey("ubicacion.longitud", $array);

        $this->assertArrayHasKey("moneda", $array);
        $this->assertArrayHasKey("redondeo", $array);
        $this->assertArrayHasKey("precio_mt2", $array);
        $this->assertArrayHasKey("precio_reserva", $array);
        $this->assertArrayHasKey("cuota_inicial", $array);
        $this->assertArrayHasKey("tasa_interes", $array);
    }
}
