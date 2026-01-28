<?php

namespace Database\Factories;

use App\Models\Tentacle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tentacle>
 */
class TentacleFactory extends Factory
{
    protected $model = Tentacle::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }
}
