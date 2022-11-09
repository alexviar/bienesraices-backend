<?php

namespace App\Listeners;

use App\Events\ReservaCreated;
use App\Models\Lote;
use App\Models\Reserva;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpdateEstadoLoteOnReservaCreated
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    private function updateReserva(Reserva $reserva)
    {
        $updated = false;
        while(!$updated && $reserva->refresh()){
            if($reserva->estado != 0){
                throw new Exception("La reserva no esta pendiente");
            }
            $updated = Reserva::where("id", $reserva->id)
                ->where("updated_at", $reserva->updated_at)
                ->update([
                    "estado" => 1
                ]);
            // if(!$updated)
            // {
            //     $reserva->refresh();
            // }
        }
    }

    private function updateLote(Lote $lote)
    {
        $updated = false;
        while(!$updated && $lote->refresh()){
            if($lote->estado != 1){
                throw new Exception("El lote no esta disponible");
            }
            $updated = Lote::where("id", $lote->id)
                ->where("updated_at", $lote->updated_at)
                ->update([
                    "estado" => 3
                ]);
            // if(!$updated)
            // {
            //     $lote->refresh();
            // }
        }
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ReservaCreated  $event
     * @return void
     */
    public function handle(ReservaCreated $event)
    {
        $reserva = $event->reserva;
        DB::transaction(function() use($reserva){
            $this->updateLote($reserva->lote);
            $this->updateReserva($reserva);
        });
    }
}
