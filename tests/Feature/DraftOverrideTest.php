<?php

namespace Tests\Feature;

use App\Models\Tentacle;
use App\Models\TentacleVideo;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SetupTentacleSchema;

class DraftOverrideTest extends TestCase
{
    use RefreshDatabase;
    use SetupTentacleSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTentacleSchema();
    }

    public function test_video_draft_true_with_no_override_returns_true(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->draft()->create();

        $this->assertTrue($video->isDraftForTentacle($tentacle));
    }

    public function test_video_draft_false_with_no_override_returns_false(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->create(['draft' => false]);

        $this->assertFalse($video->isDraftForTentacle($tentacle));
    }

    public function test_video_draft_true_overridden_to_false(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->draft()->create();

        TentacleVideo::factory()->create([
            'tentacle_id' => $tentacle->id,
            'video_id' => $video->id,
            'draft' => false,
        ]);

        $this->assertFalse($video->isDraftForTentacle($tentacle));
    }

    public function test_video_draft_false_overridden_to_true(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->create(['draft' => false]);

        TentacleVideo::factory()->create([
            'tentacle_id' => $tentacle->id,
            'video_id' => $video->id,
            'draft' => true,
        ]);

        $this->assertTrue($video->isDraftForTentacle($tentacle));
    }

    public function test_override_is_tentacle_specific(): void
    {
        $tentacleA = Tentacle::factory()->create();
        $tentacleB = Tentacle::factory()->create();
        $video = Video::factory()->draft()->create();

        TentacleVideo::factory()->create([
            'tentacle_id' => $tentacleA->id,
            'video_id' => $video->id,
            'draft' => false,
        ]);

        $this->assertFalse($video->isDraftForTentacle($tentacleA));
        $this->assertTrue($video->isDraftForTentacle($tentacleB));
    }

    public function test_tentacle_video_is_visible_when_not_draft(): void
    {
        $tv = TentacleVideo::factory()->create(['draft' => false]);

        $this->assertTrue($tv->isVisible());
    }

    public function test_tentacle_video_is_not_visible_when_draft(): void
    {
        $tv = TentacleVideo::factory()->draft()->create();

        $this->assertFalse($tv->isVisible());
    }
}
