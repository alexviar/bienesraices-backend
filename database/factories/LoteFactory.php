<?php

namespace Database\Factories;

use App\Models\CategoriaLote;
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
            "precio" => isset($attributes["precio"]) ? $attributes["precio"] : $this->faker->numerify("#####.##"),
            "geocerca" => $attributes["geocerca"] ?? null,
            "manzana_id" => $manzanaId,
            "categoria_id" => $attributes["categoria_id"] ?? CategoriaLote::factory([
                "proyecto_id" => Manzana::find($manzanaId)->plano->proyecto_id
            ])
        ];
    }
}
