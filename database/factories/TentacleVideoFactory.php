<?php

namespace Database\Factories;

use App\Models\Tentacle;
use App\Models\TentacleVideo;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TentacleVideo>
 */
class TentacleVideoFactory extends Factory
{
    protected $model = TentacleVideo::class;

    public function definition(): array
    {
        return [
            'tentacle_id' => Tentacle::factory(),
            'video_id' => Video::factory(),
            'draft' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'draft' => true,
        ]);
    }
}
