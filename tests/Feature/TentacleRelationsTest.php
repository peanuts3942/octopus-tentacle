<?php

namespace Tests\Feature;

use App\Models\OctopusUser;
use App\Models\Tentacle;
use App\Models\TentacleSetting;
use App\Models\TentacleVideo;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SetupTentacleSchema;

class TentacleRelationsTest extends TestCase
{
    use RefreshDatabase;
    use SetupTentacleSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTentacleSchema();
    }

    public function test_tentacle_has_many_settings(): void
    {
        $tentacle = Tentacle::factory()->create();
        TentacleSetting::factory()->count(3)->create(['tentacle_id' => $tentacle->id]);

        $this->assertCount(3, $tentacle->settings);
        $this->assertInstanceOf(TentacleSetting::class, $tentacle->settings->first());
    }

    public function test_tentacle_has_many_tentacle_videos(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->create();
        TentacleVideo::factory()->create([
            'tentacle_id' => $tentacle->id,
            'video_id' => $video->id,
        ]);

        $this->assertCount(1, $tentacle->tentacleVideos);
        $this->assertInstanceOf(TentacleVideo::class, $tentacle->tentacleVideos->first());
    }

    public function test_tentacle_setting_belongs_to_tentacle(): void
    {
        $tentacle = Tentacle::factory()->create();
        $setting = TentacleSetting::factory()->create(['tentacle_id' => $tentacle->id]);

        $this->assertInstanceOf(Tentacle::class, $setting->tentacle);
        $this->assertEquals($tentacle->id, $setting->tentacle->id);
    }

    public function test_tentacle_setting_casts_options_as_array(): void
    {
        $tentacle = Tentacle::factory()->create();
        $setting = TentacleSetting::factory()->create([
            'tentacle_id' => $tentacle->id,
            'name' => 'theme',
            'options' => ['color' => '#ff0000', 'logo' => 'logo.png'],
        ]);

        $setting->refresh();
        $this->assertIsArray($setting->options);
        $this->assertEquals('#ff0000', $setting->options['color']);
    }

    public function test_tentacle_setting_casts_is_active_as_boolean(): void
    {
        $tentacle = Tentacle::factory()->create();
        $setting = TentacleSetting::factory()->create([
            'tentacle_id' => $tentacle->id,
            'is_active' => 1,
        ]);

        $setting->refresh();
        $this->assertIsBool($setting->is_active);
        $this->assertTrue($setting->is_active);
    }

    public function test_tentacle_video_belongs_to_tentacle(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->create();
        $tv = TentacleVideo::factory()->create([
            'tentacle_id' => $tentacle->id,
            'video_id' => $video->id,
        ]);

        $this->assertInstanceOf(Tentacle::class, $tv->tentacle);
        $this->assertEquals($tentacle->id, $tv->tentacle->id);
    }

    public function test_tentacle_video_belongs_to_video(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->create();
        $tv = TentacleVideo::factory()->create([
            'tentacle_id' => $tentacle->id,
            'video_id' => $video->id,
        ]);

        $this->assertInstanceOf(Video::class, $tv->video);
        $this->assertEquals($video->id, $tv->video->id);
    }

    public function test_video_has_many_tentacle_videos(): void
    {
        $video = Video::factory()->create();
        $tentacle1 = Tentacle::factory()->create();
        $tentacle2 = Tentacle::factory()->create();

        TentacleVideo::factory()->create(['tentacle_id' => $tentacle1->id, 'video_id' => $video->id]);
        TentacleVideo::factory()->create(['tentacle_id' => $tentacle2->id, 'video_id' => $video->id]);

        $this->assertCount(2, $video->tentacleVideos);
    }

    public function test_deleting_tentacle_cascades_to_settings(): void
    {
        $tentacle = Tentacle::factory()->create();
        TentacleSetting::factory()->count(2)->create(['tentacle_id' => $tentacle->id]);

        $tentacle->delete();

        $this->assertDatabaseCount('tentacle_settings', 0);
    }

    public function test_deleting_tentacle_cascades_to_tentacle_videos(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->create();
        TentacleVideo::factory()->create(['tentacle_id' => $tentacle->id, 'video_id' => $video->id]);

        $tentacle->delete();

        $this->assertDatabaseCount('tentacle_video', 0);
        $this->assertDatabaseCount('videos', 1);
    }

    public function test_octopus_user_password_is_hashed(): void
    {
        $user = OctopusUser::factory()->create();

        $this->assertNotEquals('password', $user->password);
    }

    public function test_unique_constraint_tentacle_video(): void
    {
        $tentacle = Tentacle::factory()->create();
        $video = Video::factory()->create();

        TentacleVideo::factory()->create([
            'tentacle_id' => $tentacle->id,
            'video_id' => $video->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        TentacleVideo::factory()->create([
            'tentacle_id' => $tentacle->id,
            'video_id' => $video->id,
        ]);
    }
}
