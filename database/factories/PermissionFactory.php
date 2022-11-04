<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "name" => $attributes["name"] ?? $this->faker->text(80),
            "tag" => $attributes["tag"] ?? "None",
            "guard_name" => $attributes["guard_name"] ?? "sanctum"
        ];
    }
}
