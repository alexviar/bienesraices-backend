<?php

namespace Database\Factories;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "titulo" => $attributes["titulo"] ?? $this->faker->text(100),
            "descripcion" => $attributes["descripcion"] ?? $this->faker->text(255),
            "proyecto_id" => $attributes["proyecto_id"] ?? Proyecto::factory()
        ];
    }
}
