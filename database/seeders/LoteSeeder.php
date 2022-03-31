<?php

namespace Database\Seeders;

use App\Models\Lote;
use App\Models\Manzana;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class LoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(Manzana::get() as $manzana) {
            Lote::factory(rand(8, 15))->for($manzana)->create();
        };
    }
}
