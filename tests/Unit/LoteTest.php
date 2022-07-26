<?php

namespace Tests\Unit;

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Proyecto;
use Tests\TestCase;

class LoteTest extends TestCase
{
    public function test_precio_sugerido()
    {
        $proyecto = Proyecto::factory([
            "precio_mt2" => "1.91",
            "redondeo" => "100",
            "moneda" => "BOB"
        ])->create();
        $manzana = Manzana::factory()->for($proyecto)->create();
        /** @var Lote $lote */
        $lote = Lote::factory([
            "superficie" => "1000.00"
        ])->for($manzana)->create();

        $precioSugerido = $lote->precio_sugerido;

        $this->assertTrue($precioSugerido->amount->isEqualTo("2000.00"));
        $this->assertTrue($precioSugerido->currency->code === "BOB");
    }

    
    public function test_to_array()
    {
        /** @var Lote $lote */
        $lote = Lote::factory()->create();

        $array = $lote->toArray();

        $this->assertArrayHasKey("id", $array);
        $this->assertArrayHasKey("numero", $array);
        $this->assertArrayHasKey("superficie", $array);
        $this->assertArrayHasKey("precio", $array);
        $this->assertArrayHasKey("precio_sugerido", $array);
        $this->assertArrayHasKey("manzana_id", $array);
    }
}
