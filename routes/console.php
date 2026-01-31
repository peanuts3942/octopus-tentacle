<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Meilisearch Sync Commands
|--------------------------------------------------------------------------
*/

// Full sync quotidien (tous les documents)
Schedule::command('app:update-all-documents-meilisearch')
    ->daily()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/meilisearch-full-sync.log'));

// Mise à jour du statut published (toutes les 5min)
Schedule::command('videos:update-published-status-in-meilisearch --force')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/video-published-status.log'));

// Mise à jour des documents récents (toutes les 5min)
Schedule::command('app:update-recent-documents-meilisearch')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/update-recent-documents-meilisearch.log'));
