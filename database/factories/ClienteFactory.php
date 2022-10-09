<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        $tipo = $attributes["tipo"] ?? $this->faker->randomElement([1,2]);
        if($tipo === 1) {
            $tipo_documento = $attributes["tipo_documento"] ?? $this->faker->randomElement([1,2]);
            return [
                "tipo" => $tipo,
                "tipo_documento" => $tipo_documento,
                "numero_documento" => $attributes["numero_documento"] ?? ($tipo_documento == 1 ? $this->faker->bothify("#######-?#") : $this->faker->bothify("##########")),
                "apellido_paterno" => isset($attributes["apellido_paterno"]) ? $attributes["apellido_paterno"] : $this->faker->lastName,
                "apellido_materno" => isset($attributes["apellido_materno"]) ? $attributes["apellido_materno"] : $this->faker->lastName,
                "nombre" => $attributes["nombre"] ?? $this->faker->firstName,
                "telefono" => $attributes["telefono"] ?? $this->faker->numerify("########")
            ];
        }
        else {
            return [
                "tipo" => $tipo,
                "tipo_documento" => 2,
                "numero_documento" => $attributes["numero_documento"] ?? $this->faker->bothify("##########"),
                "nombre" => $attributes["nombre"] ?? $this->faker->company,
                "telefono" => $attributes["telefono"] ?? $this->faker->numerify("########")
            ];
        }
    }
}
