<?php

namespace Database\Factories;

use App\Models\Tentacle;
use App\Models\TentacleSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TentacleSetting>
 */
class TentacleSettingFactory extends Factory
{
    protected $model = TentacleSetting::class;

    public function definition(): array
    {
        return [
            'tentacle_id' => Tentacle::factory(),
            'name' => fake()->word(),
            'options' => ['key' => fake()->word()],
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
