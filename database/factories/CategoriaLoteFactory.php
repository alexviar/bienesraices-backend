<?php

namespace Database\Factories;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaLoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "codigo" => $attributes["codigo"] ?? $this->faker->lexify(),
            "descripcion" => $attributes["descripcion"] ?? $this->faker->text(255),
            "precio_m2" => $attributes["precio_m2"] ?? $this->faker->numerify('#.##'),
            "proyecto_id" => $attributes["proyecto_id"] ?? Proyecto::factory()
        ];
    }
}
