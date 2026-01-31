<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateRecentDocumentsMeilisearch extends Command
{
    protected $signature = 'app:update-recent-documents-meilisearch
        {--limit=300 : Nombre de documents récents à traiter}
        {--index= : Spécifier un index spécifique (videos, channels)}
        {--nodelete : Ne pas supprimer les documents obsolètes}';

    protected $description = 'Update recent documents in Meilisearch indexes (videos and channels). Use --limit to specify how many recent documents to process.';

    public function handle(): int
    {
        $zone = config('app.zone');
        app()->setLocale($zone);

        $this->info('Starting update of recent Meilisearch documents...');
        $startTime = microtime(true);

        $specificIndex = $this->option('index');
        $noDelete = $this->option('nodelete');
        $limit = (int) $this->option('limit');

        try {
            if ($limit <= 0) {
                $this->error('La limite doit être un nombre positif.');

                return Command::FAILURE;
            }

            if ($specificIndex && ! in_array($specificIndex, ['videos', 'channels'])) {
                $this->error('Index invalide. Les valeurs possibles sont : videos, channels');

                return Command::FAILURE;
            }

            $client = app(\Meilisearch\Client::class);

            $indexes = [];
            if (! $specificIndex || $specificIndex === 'videos') {
                $indexes['videos'] = $client->index('videos');
            }
            if (! $specificIndex || $specificIndex === 'channels') {
                $indexes['channels'] = $client->index('channels');
            }

            foreach ($indexes as $name => $index) {
                $this->info("\n=== MISE À JOUR DES {$limit} DERNIERS DOCUMENTS DE L'INDEX {$name} ===");

                match ($name) {
                    'videos' => $this->updateRecentVideos($index, $limit, $noDelete),
                    'channels' => $this->updateRecentChannels($index, $limit, $noDelete),
                };
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->info("\n✓ Mise à jour des documents récents Meilisearch terminée.");
            $this->info("Meilisearch update completed in {$executionTime} seconds.");

            Log::info('Meilisearch recent documents update completed', [
                'execution_time' => $executionTime,
                'limit' => $limit,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error updating recent documents in Meilisearch: '.$e->getMessage());
            Log::error('Error updating recent documents in Meilisearch', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }

    private function updateRecentVideos($videosIndex, int $limit, bool $noDelete = false): void
    {
        try {
            $batchSize = 100;
            $addedVideos = 0;
            $updatedVideos = 0;
            $deletedVideos = 0;
            $deletedIds = [];

            // Récupérer les N dernières vidéos indexables depuis la DB
            $recentVideos = Video::indexable()
                ->with([
                    'channel.aliases',
                    'tags.translations',
                    'translation',
                ])
                ->orderBy('videos.id', 'desc')
                ->limit($limit)
                ->get();

            if ($recentVideos->isEmpty()) {
                $this->info('Aucune vidéo trouvée dans la base de données.');

                return;
            }

            $dbIds = array_map('intval', $recentVideos->pluck('id')->toArray());
            $minId = min($dbIds);
            $maxId = max($dbIds);

            $this->line("ID range dans la DB: {$minId} - {$maxId}");

            // Récupérer les IDs existants dans Meilisearch pour ce range
            $rangeLimit = ($maxId - $minId + 1) * 2;

            $searchResults = $videosIndex->search('', [
                'filter' => "id >= {$minId} AND id <= {$maxId}",
                'limit' => $rangeLimit,
            ]);

            $meilisearchIds = array_map('intval', collect($searchResults->getHits())->pluck('id')->toArray());

            // Distinguer les ajouts des mises à jour
            $idsToAdd = array_diff($dbIds, $meilisearchIds);

            // Préparer les documents
            $documentsToUpdate = [];
            foreach ($recentVideos as $video) {
                $documentsToUpdate[] = $video->toSearchableArray();
                if (in_array((int) $video->id, $idsToAdd, true)) {
                    $addedVideos++;
                } else {
                    $updatedVideos++;
                }
            }

            // Traiter par lots
            $chunks = array_chunk($documentsToUpdate, $batchSize);
            foreach ($chunks as $chunkIndex => $chunk) {
                $videosIndex->updateDocuments(array_values($chunk));
                $this->line('Batch '.($chunkIndex + 1).'/'.count($chunks).' processed: '.count($chunk).' videos');
                if ($chunkIndex < count($chunks) - 1) {
                    sleep(1);
                }
            }

            // Gérer les suppressions
            if (! $noDelete) {
                $idsToDelete = array_values(array_diff($meilisearchIds, $dbIds));

                if (! empty($idsToDelete)) {
                    $videosIndex->deleteDocuments($idsToDelete);
                    $deletedVideos = count($idsToDelete);
                    $deletedIds = $idsToDelete;
                    $this->line('Suppression de '.count($idsToDelete).' vidéos obsolètes...');
                }
            }

            $this->info("✓ Videos ajoutés : {$addedVideos} | Mis à jour : {$updatedVideos} | Supprimés : {$deletedVideos}");
            if (! empty($deletedIds)) {
                $this->info('IDs supprimés : '.implode(', ', $deletedIds));
            }

        } catch (\Exception $e) {
            $this->error('Error updating recent videos in Meilisearch: '.$e->getMessage());
            Log::error('Error updating recent videos in Meilisearch', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateRecentChannels($channelsIndex, int $limit, bool $noDelete = false): void
    {
        try {
            $batchSize = 100;
            $addedChannels = 0;
            $updatedChannels = 0;
            $deletedChannels = 0;
            $deletedIds = [];

            // Récupérer les N derniers channels indexables depuis la DB
            $recentChannels = Channel::indexable()
                ->with('aliases')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            if ($recentChannels->isEmpty()) {
                $this->info('Aucun channel trouvé dans la base de données.');

                return;
            }

            $dbIds = array_map('intval', $recentChannels->pluck('id')->toArray());
            $minId = min($dbIds);
            $maxId = max($dbIds);

            $this->line("ID range dans la DB: {$minId} - {$maxId}");

            // Récupérer les IDs existants dans Meilisearch pour ce range
            $rangeLimit = ($maxId - $minId + 1) * 2;

            $searchResults = $channelsIndex->search('', [
                'filter' => "id >= {$minId} AND id <= {$maxId}",
                'limit' => $rangeLimit,
            ]);

            $meilisearchIds = array_map('intval', collect($searchResults->getHits())->pluck('id')->toArray());

            // Distinguer les ajouts des mises à jour
            $idsToAdd = array_diff($dbIds, $meilisearchIds);

            // Préparer les documents
            $documentsToUpdate = [];
            foreach ($recentChannels as $channel) {
                $documentsToUpdate[] = $channel->toSearchableArray();
                if (in_array((int) $channel->id, $idsToAdd, true)) {
                    $addedChannels++;
                } else {
                    $updatedChannels++;
                }
            }

            // Traiter par lots
            $chunks = array_chunk($documentsToUpdate, $batchSize);
            foreach ($chunks as $chunkIndex => $chunk) {
                $channelsIndex->updateDocuments(array_values($chunk));
                $this->line('Batch '.($chunkIndex + 1).'/'.count($chunks).' processed: '.count($chunk).' channels');
                if ($chunkIndex < count($chunks) - 1) {
                    sleep(1);
                }
            }

            // Gérer les suppressions
            if (! $noDelete) {
                $idsToDelete = array_values(array_diff($meilisearchIds, $dbIds));

                if (! empty($idsToDelete)) {
                    $channelsIndex->deleteDocuments($idsToDelete);
                    $deletedChannels = count($idsToDelete);
                    $deletedIds = $idsToDelete;
                    $this->line('Suppression de '.count($idsToDelete).' channels obsolètes...');
                }
            }

            $this->info("✓ Channels ajoutés : {$addedChannels} | Mis à jour : {$updatedChannels} | Supprimés : {$deletedChannels}");
            if (! empty($deletedIds)) {
                $this->info('IDs supprimés : '.implode(', ', $deletedIds));
            }

        } catch (\Exception $e) {
            $this->error('Error updating recent channels in Meilisearch: '.$e->getMessage());
            Log::error('Error updating recent channels in Meilisearch', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
