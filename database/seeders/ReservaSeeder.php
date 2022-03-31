<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // for($i = 0; $i < 10; $i++){
        //     Reserva::factory()
        //         ->vencida()
        //         ->for(Lote::inRandomOrder()->first())
        //         ->for(Cliente::inRandomOrder()->first())
        //         ->for(Vendedor::inRandomOrder()->first())
        //         ->create();
        // }
    }
}
