<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "vencimiento" => $attributes["vencimiento"] ?? $this->faker->date(),
            "numero" => $attributes["moneda"] ?? $this->faker->numberBetween(1,48),
            "importe" => $attributes["importe"] ?? $this->faker->numerify("###.##"),
            "saldo" => $attributes["saldo"] ?? "0",
            "saldo_capital" => $attributes["saldo_capital"] ?? "0",
            "codigo" => $this->faker->numerify("########"),
            "credito_id" => $attributes["credito_id"],
        ];
    }
}
