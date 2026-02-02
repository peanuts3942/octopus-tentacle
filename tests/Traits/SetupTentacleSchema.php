<?php

namespace Tests\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the tentacle-related tables in the test database.
 *
 * Only tables owned by octopus-tentacle are created here.
 * Tables like videos, channels, tags belong to btbf/ and are not tested here.
 */
trait SetupTentacleSchema
{
    protected function setUpTentacleSchema(): void
    {
        Schema::create('tentacles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tentacle_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentacle_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->json('options')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('octopus_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
