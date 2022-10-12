<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Reserva;
use App\Models\Vendedor;
use App\Models\Venta;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class CreditoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        // if(($creditableId = Arr::get($attributes, "creditable_id")) && ($creditableType = Arr::get($attributes, "creditable_type"))){
        //     $creditableId = $this->resolveAttribute($creditableId, []);
        //     $creditableType = $this->resolveAttribute($creditableType, []);
        //     $creditable = $creditableType::find($creditableId);
        // }
        // else {
        //     throw new Exception("Debe inidicar el creditable");
        // }

        // $importe = $creditable->importe;
        
        $periodoPago = $attributes["periodo_pago"] ?? $this->faker->randomElement([1,2,3,4,6]);
        $plazo = $attributes["plazo"] ?? $this->faker->randomElement([12, 24, 36, 48, 60]);
        return [
            // "moneda" => $importe->currency->code,
            // "importe" => (string) $importe->amount,
            "codigo" => $attributes["codigo"] ?? $this->faker->unique()->randomNumber(),
            // "cuota_inicial" => $attributes["cuota_inicial"] ?? "500.00",
            "importe_cuotas" => $attributes["importe_cuotas"] ?? "500.00",
            "tasa_interes" => $attributes["tasa_interes"] ?? "0.1000",
            "tasa_mora" => "0.0300",
            "plazo" => $plazo,
            "periodo_pago" => $periodoPago,
            "dia_pago" => $attributes["dia_pago"] ?? $this->faker->numberBetween(1, 31),
            "creditable_id" => $attributes["creditable_id"] ?? null,
            "creditable_type" => $attributes["creditable_type"] ?? null,
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
