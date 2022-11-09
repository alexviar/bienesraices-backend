<?php

namespace App\Console\Commands;

use App\Models\Lote;
use App\Models\Reserva;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ReleaseLotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservas:liberar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libera los lotes de reservas vencidas de modo que esten disponibles para futuras ventas o reservas.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return Lote::joinSub(
            Reserva::where("vencimiento", "<", Carbon::today()->format("Y-m-d")),
            "reservas_vencidas", 
            function ($join) {
                $join->on('lotes.id', '=', 'reservas_vencidas.lote_id');
            }
        )
        ->update([
            "lotes.estado" => 1
        ]);
    }
}
