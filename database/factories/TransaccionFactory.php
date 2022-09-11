<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
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
            "metodo_pago" => $attributes["forma_pago"] ?? $this->faker->randomElement([1,2]),
            
            // "cliente_id" => $attributes["cliente_id"] ?? Cliente::factory()
            "user_id" => $attributes["user_id"] ?? User::find(1)
        ];
    }
}
