<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Vendedor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        $loteId = $this->resolveAttribute($attributes["lote_id"] ?? Lote::factory(), []);
        $lote = Lote::find($loteId);
        $proyecto = $lote->manzana->proyecto;
        $fecha = $this->resolveAttribute($attributes["fecha"] ?? $this->faker->date, []);
        return [
            "fecha" => $fecha,
            "vencimiento" => $attributes["vencimiento"] ?? Carbon::parse($fecha)->addDays($proyecto->duracionReservas)->format("Y-m-d"),
            "importe" => $attributes["importe"] ?? $proyecto->getAttributeFromArray("precio_reserva"),
            "saldo_credito" => $attributes["saldo_credito"] ?? $proyecto->getAttributeFromArray("saldo_credito"),
            "saldo_contado" => $attributes["saldo_contado"] ?? $proyecto->getAttributeFromArray("saldo_contado"),
            "proyecto_id" => $proyecto,
            "lote_id" => $loteId,
            "cliente_id" => $attributes["cliente_id"] ?? Cliente::factory(),
            "vendedor_id" => $attributes["vendedor_id"] ?? Vendedor::factory(),
        ];
    }

    function vencida(){
        $fecha = $this->faker->date(null, Carbon::now()->sub(8)->format("Y-m-d"));
        return $this->state([
            "fecha" => $fecha,
            "vencimiento" => Carbon::parse($fecha)->addDays(7)->format("Y-m-d")
        ]);
    }
}
