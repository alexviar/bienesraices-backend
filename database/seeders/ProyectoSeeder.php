<?php

namespace Database\Seeders;

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class ProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $proyecto = Proyecto::factory([
            "tasa_interes" => "0.1",
            "cuota_inicial" => "500"
        ])->create();
        $manzana = Manzana::factory()->for($proyecto)->create();
        Lote::factory([
            "precio" => "10530.96"
        ])->for($manzana)->create();

        Proyecto::factory(10)->create();
    }
}
