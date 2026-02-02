<?php

namespace Tests\Feature;

use App\Models\OctopusUser;
use App\Models\Tentacle;
use App\Models\TentacleSetting;
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

    public function test_deleting_tentacle_cascades_to_settings(): void
    {
        $tentacle = Tentacle::factory()->create();
        TentacleSetting::factory()->count(2)->create(['tentacle_id' => $tentacle->id]);

        $tentacle->delete();

        $this->assertDatabaseCount('tentacle_settings', 0);
    }

    public function test_octopus_user_password_is_hashed(): void
    {
        $user = OctopusUser::factory()->create();

        $this->assertNotEquals('password', $user->password);
    }
}
