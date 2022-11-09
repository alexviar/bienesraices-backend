<?php

namespace App\Listeners;

use App\Events\VentaCreated;
use App\Models\Lote;
use App\Models\Venta;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpdateEstadoLoteOnVentaCreated
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

    private function updateReserva(Venta $venta)
    {
        $updated = false;
        while(!$updated && $venta->refresh()){
            if($venta->estado != 0){
                throw new Exception("La venta no esta pendiente");
            }
            $updated = Venta::where("id", $venta->id)
                ->where("updated_at", $venta->updated_at)
                ->update([
                    "estado" => 1
                ]);
            // if(!$updated)
            // {
            //     $venta->refresh();
            // }
        }
    }

    private function updateLote(Venta $venta)
    {
        $lote = $venta->lote;
        $updated = false;
        while(!$updated && $lote->refresh()){
            if($lote->estado != 1 && !($lote->estado == 3 && $lote->reserva->cliente_id == $venta->cliente_id)){
                throw new Exception("El lote no esta disponible");
            }
            $updated = Lote::where("id", $lote->id)
                ->where("updated_at", $lote->updated_at)
                ->update([
                    "estado" => 4
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
     * @param  \App\Events\VentaCreated  $event
     * @return void
     */
    public function handle(VentaCreated $event)
    {
        $venta = $event->venta;
        DB::transaction(function() use($venta){
            $this->updateLote($venta);
            $this->updateReserva($venta);
        });
    }
}
