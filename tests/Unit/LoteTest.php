<?php

namespace Tests\Unit;

use App\Models\CategoriaLote;
use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Proyecto;
use Tests\TestCase;

class LoteTest extends TestCase
{
    public function test_precio_sugerido()
    {
        $proyecto = Proyecto::factory([
            "redondeo" => "100",
            "moneda" => "BOB"
        ])->create();
        $manzana = Manzana::factory()->for($proyecto)->create();
        /** @var Lote $lote */
        $lote = Lote::factory([
            "superficie" => "1000.00"
        ])->for($manzana)->for(CategoriaLote::factory([
            "precio_m2" => "1.91"
        ])->for($proyecto), "categoria")->create();

        $precioSugerido = $lote->precio_sugerido;

        expect((string) $precioSugerido->amount)->toBe("2000.00");
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
