<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "name" => $attributes["name"] ?? $this->faker->text(100),
            "description" => $attributes["description"] ?? $this->faker->text(255),
            "guard_name" => "sanctum"
        ];
    }
}
