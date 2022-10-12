<?php

namespace Database\Factories;

use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProyectoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "nombre" => $attributes["nombre"] ?? $this->faker->text(100),
            // "socio" => $attributes["socio"] ?? ($this->faker->lastName . " " . $this->faker->lastName . " " . $this->faker->firstName),
            "ubicacion" => $attributes["socio"] ?? new Point($this->faker->latitude, $this->faker->longitude),

            "moneda" => $attributes["moneda"] ?? "USD",
            "redondeo" => 100,
            // "regateo" => 500,
            // "precio_mt2" => $attributes["precio_mt2"] ?? $this->faker->numerify("#.##"),
            "precio_reservas" => $attributes["precio_reserva"] ?? $this->faker->numerify("###.##"),
            "duracion_reservas" => $attributes["precio_reserva"] ?? $this->faker->numberBetween(7, 30),
            "cuota_inicial" => $attributes["cuota_inicial"] ?? $this->faker->numerify("###.##"),
            "tasa_interes" => $attributes["tasa_interes"] ?? $this->faker->numerify("0.1###"),
            "tasa_mora" => $attributes["tasa_mora"] ?? "0.03",
        ];
    }
}
