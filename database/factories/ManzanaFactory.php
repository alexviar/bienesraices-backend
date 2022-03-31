<?php

namespace Database\Factories;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ManzanaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        $proyecto = $this->resolveAttribute($attributes["proyecto_id"] ?? Proyecto::factory(), []);
        $proyectoId = $proyecto instanceof Model ? $proyecto->id : $proyecto;
        return [
            "numero" => $attributes["numero"] ?? explode("-", $this->faker->unique()->numerify("P{$proyectoId}-##"))[1],
            "proyecto_id" => $proyectoId
        ];
    }
}
