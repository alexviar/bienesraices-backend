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
        $tipo = $attributes["tipo"] ?? $this->faker->randomElement([1,2]);

        $reservaId = $this->resolveAttribute(isset($attributes["reserva_id"]) ? $attributes["reserva_id"] : optional(Reserva::factory())->value, []);
        $reserva = Reserva::find($reservaId);
        $loteId = $this->resolveAttribute($reserva->lote ?? $attributes["lote_id"] ?? Lote::factory(), []);
        $lote = Lote::find($loteId);
        $proyecto = $lote->manzana->proyecto;

        $base = [
            "tipo" => $tipo,
            "fecha" => $attributes["fecha"] ?? $this->faker->date(),
            "moneda" => $attributes["moneda"] ?? $proyecto->moneda,
            "lote_id" => $lote->id,
            "importe" => $attributes["importe"] ?? $lote->getAttributes()["precio"] ?? (string) $lote->precio_sugerido->amount,
            "estado" => $attributes["estado"] ?? $this->faker->randomElement([1,2]),
            "cliente_id" => $reserva->cliente ?? $attributes["cliente_id"] ?? Cliente::factory(),
            "vendedor_id" => $reserva->vendedor ?? $attributes["vendedor_id"] ?? Vendedor::factory(),
            "proyecto_id" => $proyecto,
            "reserva_id" => $reserva->id ?? null
        ];
        if($tipo === 1) return $base;

        $periodoPago = $attributes["periodo_pago"] ?? $this->faker->randomElement([1,2,3,4,6]);
        $plazo = $attributes["plazo"] ?? $this->faker->randomElement([12, 24, 36, 48]);
        return $base + [
            "cuota_inicial" => $attributes["cuota_inicial"] ?? $proyecto->cuota_inicial,
            "tasa_interes" => $attributes["tasa_interes"] ?? $proyecto->tasa_interes,
            "tasa_mora" => "0.03",
            // "plazo" => $attributes["plazo"] ?? ($this->faker->numberBetween(2,10)*$periodoPago),
            "plazo" => $plazo,
            "periodo_pago" => $periodoPago
        ];
    }

    public function contado(){
        return $this->state([
            "tipo" => 1
        ]);
    }

    public function credito(){
        return $this->state([
            "tipo" => 2
        ]);
    }

    /**
     * @param bool $with
     */
    public function withReserva($with = true){
        return $this->state([
            "reserva_id" => $with ? Reserva::factory() : null
        ]);
    }
}
