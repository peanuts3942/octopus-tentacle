<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePublishedVideosInMeilisearch extends Command
{
    protected $signature = 'videos:update-published-status-in-meilisearch {--force : Skip confirmation}';

    protected $description = 'Met à jour is_published dans Meilisearch pour les vidéos qui sont maintenant publiées';

    public function handle(): int
    {
        $this->info('Début de la synchronisation is_published dans Meilisearch...');
        $startTime = microtime(true);

        try {
            $client = app(\Meilisearch\Client::class);
            $videosIndex = $client->index('videos');

            $this->info('Récupération des vidéos non publiées dans Meilisearch...');

            $unpublishedVideos = $videosIndex->search('', [
                'filter' => ['is_published = false'],
                'limit' => 1000,
                'attributesToRetrieve' => ['id'],
            ]);

            if (empty($unpublishedVideos->getHits())) {
                $this->info('Aucune vidéo non publiée trouvée dans Meilisearch');

                return self::SUCCESS;
            }

            $videoIds = collect($unpublishedVideos->getHits())->pluck('id')->toArray();

            $this->line("\n".str_repeat('-', 50));
            $this->line('VIDÉOS NON PUBLIÉES TROUVÉES DANS MEILISEARCH');
            $this->line(str_repeat('-', 50));
            $this->line(count($videoIds).' vidéo(s) trouvée(s)');
            $this->line(str_repeat('-', 50));

            // Vérifier en base quelles vidéos sont maintenant publiées
            $this->info('Vérification du statut de publication en base de données...');

            $publishedVideos = DB::table('videos')
                ->whereIn('id', $videoIds)
                ->where('is_published', true)
                ->pluck('id')
                ->toArray();

            if (empty($publishedVideos)) {
                $this->info('Aucune vidéo à mettre à jour dans Meilisearch');

                return self::SUCCESS;
            }

            $this->line("\n".str_repeat('-', 50));
            $this->line('VIDÉOS MAINTENANT PUBLIÉES EN BASE DE DONNÉES');
            $this->line(str_repeat('-', 50));
            $this->line(count($publishedVideos).' vidéo(s)');
            $this->line(str_repeat('-', 50));

            if (! $this->option('force')) {
                if (! $this->confirm('Voulez-vous mettre à jour '.count($publishedVideos).' vidéo(s) dans Meilisearch ?')) {
                    $this->info('Mise à jour Meilisearch annulée.');

                    return self::SUCCESS;
                }
            }

            $this->info('Mise à jour des documents dans Meilisearch...');

            $videoUpdates = collect($publishedVideos)->map(fn ($videoId) => [
                'id' => $videoId,
                'is_published' => true,
            ])->toArray();

            $videosIndex->updateDocuments($videoUpdates);

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->info(count($publishedVideos).' vidéo(s) mise(s) à jour dans Meilisearch');
            $this->info("Temps d'exécution : {$executionTime} secondes");

            Log::info('UpdatePublishedVideosInMeilisearch completed', [
                'updated_count' => count($publishedVideos),
                'execution_time' => $executionTime,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Erreur lors de la synchronisation : '.$e->getMessage());
            Log::error('UpdatePublishedVideosInMeilisearch failed', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
