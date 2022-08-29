<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PagoExtraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "tipo_ajuste" => $attributes["tipo_ajuste"] ?? 1,
            "periodo" => $attributes["periodo"] ?? 1,
            "importe" => $attributes["importe"] ?? "1000",
            "credito_id" => $attributes["credito_id"]
        ];
    }
}
