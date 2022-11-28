<?php

namespace App\Console\Commands;

use App\Models\Lote;
use App\Models\Reserva;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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
        $today = Carbon::today();
        $result = Lote::join(
            "reservas",
            'lotes.id',
            'reservas.lote_id'
        )->where("reservas.vencimiento", "<", $today->format("Y-m-d"))
            ->where("reservas.estado", 1)
            ->update([
                "lotes.estado" => 1,
                "reservas.estado" => 4,
            ]);

        Log::debug("$result lotes liberados por reservas vencidas");
    }
}
