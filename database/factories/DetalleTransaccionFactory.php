<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleTransaccionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "moneda" => $attributes["moneda"] ?? $this->faker->randomElement(["USD", "BOB"]),
            "importe" => $attributes["importe"] ?? $this->faker->numerify("###.##"),
            "transaccion_id" => $attributes["transaccion_id"],
            "transactable_id" => $attributes["transactable_id"],
            "transactable_type" => $attributes["transactable_type"],
        ];
    }
}
