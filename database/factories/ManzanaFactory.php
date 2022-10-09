<?php

namespace Database\Factories;

use App\Models\Plano;
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
        $plano = $this->resolveAttribute($attributes["plano_id"] ?? Plano::factory(), []);
        $planoId = $plano instanceof Model ? $plano->id : $plano;
        return [
            "numero" => $attributes["numero"] ?? explode("-", $this->faker->unique()->numerify("P{$plano}-##"))[1],
            "plano_id" => $planoId
        ];
    }
}
