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
            "pagable_id" => $attributes["pagable_id"],
            "pagable_type" => $attributes["pagable_type"],
        ];
    }
}
