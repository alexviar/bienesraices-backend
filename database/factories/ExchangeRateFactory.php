<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        return [
            "valid_from" => $attributes["valid_from"] ?? $this->faker->date(),
            "source" => $attributes["source"] ?? Currency::factory(),
            "target" => $attributes["target"] ?? Currency::factory(),
            "rate" => $attributes["rate"] ?? $this->faker->numerify("##.####"),
            "indirect" => $attributes["indirect"] ?? false
        ];
    }
}
