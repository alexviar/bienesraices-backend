<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepositoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "fecha" => $attributes["fecha"] ?? $this->faker->date("Y-m-d"),
            "moneda" => $attributes["moneda"] ?? $this->faker->randomElement(["USD", "BOB"]),
            "importe" => $attributes["importe"] ?? $this->faker->numerify("###.##"),
            "saldo" => $attributes["saldo"] ?? $this->faker->numerify("###.##"),
            "numero_transaccion" => $attributes["numero_transaccion"] ?? $this->faker->unique()->randomNumber(5, true),
            "comprobante" => $attributes["comprobante"] ?? $this->faker->filePath(),
            "cliente_id" => $attributes["cliente_id"] ?? Cliente::factory() 
        ];
    }
}
