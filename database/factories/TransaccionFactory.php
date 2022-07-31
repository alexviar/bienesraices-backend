<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransaccionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "fecha" => $attributes["fecha"] ?? $this->faker->date(),
            "moneda" => $attributes["moneda"] ?? $this->faker->randomElement(["USD", "BOB"]),
            "importe" => $attributes["importe"] ?? $this->faker->numerify("###.##"),
            "forma_pago" => $attributes["forma_pago"] ?? $this->faker->randomElement([1,2]),
            "numero_transaccion" => $attributes["numero_transaccion"] ?? $this->faker->randomNumber(5, true),
            "comprobante" => $attributes["comprobante"] ?? $this->faker->filePath()
        ];
    }
}
