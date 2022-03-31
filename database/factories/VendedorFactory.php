<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VendedorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "numero_documento" => $attributes["numero_documento"] ?? $this->faker->bothify("#######-?#"),
            "apellido_paterno" => isset($attributes["apellido_paterno"]) ? $attributes["apellido_paterno"] : $this->faker->lastName,
            "apellido_materno" => isset($attributes["apellido_materno"]) ? $attributes["apellido_materno"] : $this->faker->lastName,
            "nombre" => $attributes["nombre"] ?? $this->faker->firstName,
            "telefono" => $attributes["telefono"] ?? $this->faker->numerify("########")
        ];
    }
}
