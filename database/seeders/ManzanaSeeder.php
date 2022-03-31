<?php

namespace Database\Seeders;

use App\Models\Manzana;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class ManzanaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(Proyecto::get() as $proyecto){
            Manzana::factory(rand(10, 20))->for($proyecto)->create();
        };
    }
}
