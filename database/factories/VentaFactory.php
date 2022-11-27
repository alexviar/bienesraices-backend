<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Vendedor;
use Brick\Math\BigDecimal;
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
        $loteId = $this->resolveAttribute($reserva->lote ?? $attributes["lote_id"] ?? Lote::factory([
            "estado" => 4
        ]), []);
        $lote = Lote::find($loteId);
        $proyecto = $lote->manzana->proyecto;
        $importe = $attributes["importe"] ?? $lote->getAttributes()["precio"] ?? (string) $lote->precio_sugerido->amount;

        return [
            "tipo" => $tipo,
            "fecha" => $attributes["fecha"] ?? $this->faker->date(),
            "moneda" => $attributes["moneda"] ?? $proyecto->moneda,
            "lote_id" => $lote->id,
            "importe" => $importe,
            "importe_pendiente" => $attributes["importe_pendiente"] ??  "0.00",
            "saldo" => $attributes["saldo"] ?? $importe,
            "estado" => $attributes["estado"] ?? $this->faker->randomElement([1,2]),
            "cliente_id" => $reserva->cliente ?? $attributes["cliente_id"] ?? Cliente::factory(),
            "vendedor_id" => $reserva->vendedor ?? $attributes["vendedor_id"] ?? Vendedor::factory(),
            "proyecto_id" => $proyecto,
            "reserva_id" => $reserva->id ?? null
        ];
    }

    public function contado(){
        return $this->state([
            "tipo" => 1,
            "importe_pendiente" => "0.00"
        ]);
    }

    public function credito($importePendiente){
        return $this->state([
            "tipo" => 2,
            "importe_pendiente" => $importePendiente
        ]);
    }

    function withoutReserva(){
        return $this->state([
            "reserva_id" => null
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
