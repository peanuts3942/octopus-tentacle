<?php

namespace Database\Seeders;

use App\Models\OctopusUser;
use Illuminate\Database\Seeder;

class OctopusUserSeeder extends Seeder
{
    public function run(): void
    {
        OctopusUser::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@octopus.dev',
        ]);
    }
}
