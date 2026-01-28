<?php

namespace Tests\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the tentacle-related tables in the test database.
 *
 * Migrations live in btbf/, so this trait replicates
 * the schema for octopus-tentacle's isolated test suite.
 */
trait SetupTentacleSchema
{
    protected function setUpTentacleSchema(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->boolean('draft')->default(false);
            $table->timestamps();
        });

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

        Schema::create('tentacle_video', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tentacle_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->boolean('draft')->default(false);
            $table->unique(['tentacle_id', 'video_id']);
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
