<?php

namespace Database\Factories;

use App\Models\Manzana;
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition($attributes)
    {
        $manzanaId = $this->resolveAttribute($attributes["manzana_id"] ?? Manzana::factory(), []);
        return [
            "numero" => $attributes["numero"] ?? explode("-", $this->faker->unique()->numerify("Mz{$manzanaId}-##"))[1],
            "superficie" => $attributes["superficie"] ?? ($this->faker->randomElement(["", "1"]).$this->faker->numerify("###.##")),
            "precio" => isset($attributes["precio"]) ? $attributes["precio"] : $this->faker->optional()->numerify("#####.##"),
            "geocerca" => $attributes["geocerca"] ?? new Polygon([
                new LineString([
                    new Point(1, -1),
                    new Point(1, 1),
                    new Point(-1, 1),
                    new Point(-1, -1),
                    new Point(1, -1)
                ])
            ]),
            "manzana_id" => $manzanaId,
        ];
    }
}
