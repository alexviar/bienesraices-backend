<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Vendedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        $tipo = $this->faker->randomElement([1,2]);
        
        $reservaId = $this->resolveAttribute($attributes["reserva_id"] ?? optional(Reserva::factory())->value, []);
        $reserva = Reserva::find($reservaId);
        $loteId = $this->resolveAttribute($reserva->lote ?? $attributes["lote_id"] ?? Lote::factory(), []);
        $lote = Lote::find($loteId);
        $proyecto = $lote->manzana->proyecto;

        $base = [
            "tipo" => $tipo,
            "fecha" => $this->faker->date(),
            "moneda" => $attributes["moneda"] ?? $proyecto->moneda,
            "lote_id" => $lote->id,
            "precio" => $attributes["precio"] ?? $lote->getAttributes()["precio"],
            "estado" => $attributes["estado"] ?? $this->faker->randomElement([1,2]),
            "cliente_id" => $reserva->cliente ?? $attributes["cliente_id"] ?? Cliente::factory(),
            "vendedor_id" => $reserva->vendedor ?? $attributes["vendedor_id"] ?? Vendedor::factory(),
            "proyecto_id" => $proyecto,
            "reserva_id" => $reserva->id
        ];
        if($tipo === 1) return $base;

        $periodoPago = $this->faker->randomElement([1,2,3,4,6]);
        return $base + [
            "cuota_inicial" => $attributes["cuota_inicial"] ?? $proyecto->cuota_inicial,
            "tasa_interes" => $attributes["tasa_interes"] ?? $proyecto->cuota_inicial,
            "plazo" => $attributes["plazo"] ?? ($this->faker->numberBetween(2,10)*$periodoPago),
            "periodo_pago" => $attributes["periodo_pago"] ?? $periodoPago
        ];
    }
}
