<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateAllDocumentsMeilisearch extends Command
{
    protected $signature = 'app:update-all-documents-meilisearch
        {--index= : Spécifier un index spécifique (channels, videos)}
        {--nodelete : Ne supprime PAS, garde les documents orphelins}
        {--videos-channel= : Mettre à jour uniquement les vidéos d\'une chaîne spécifique}';

    protected $description = 'Update all documents in Meilisearch indexes (channels, videos). Use --index option to update a specific index only.';

    public function handle(): int
    {
        app()->setLocale(config('app.zone'));

        $this->info('Starting update of Meilisearch indexes...');
        $startTime = microtime(true);

        try {
            $client = app(\Meilisearch\Client::class);

            $specificIndex = $this->option('index');
            $noDelete = $this->option('nodelete');
            $videosChannelId = $this->option('videos-channel');

            if ($specificIndex && ! in_array($specificIndex, ['channels', 'videos'])) {
                $this->error('Index invalide. Les valeurs possibles sont : channels, videos');

                return Command::FAILURE;
            }

            $indexes = [];

            if ($videosChannelId !== null) {
                $indexes['videos'] = $client->index('videos');
            } else {
                if (! $specificIndex || $specificIndex === 'videos') {
                    $indexes['videos'] = $client->index('videos');
                }
                if (! $specificIndex || $specificIndex === 'channels') {
                    $indexes['channels'] = $client->index('channels');
                }
            }

            foreach ($indexes as $name => $index) {
                $this->info("\n=== MISE À JOUR DE L'INDEX {$name} ===");

                match ($name) {
                    'videos' => $videosChannelId !== null
                        ? $this->updateVideosOfChannel($index, $videosChannelId, $noDelete)
                        : $this->updateVideos($index, $noDelete),
                    'channels' => $this->updateChannels($index, $noDelete),
                };
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            $this->info("\n✓ Mise à jour Meilisearch terminée.");
            $this->info("Meilisearch update completed in {$executionTime} seconds.");
            Log::info("Meilisearch update completed in {$executionTime} seconds.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error updating all documents in Meilisearch: '.$e->getMessage());
            Log::error('Error updating all documents in Meilisearch: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function updateChannels($channelsIndex, bool $noDelete = false): void
    {
        try {
            $batchSize = 100;
            $offset = 0;
            $updatedChannels = 0;
            $addedChannels = 0;
            $deletedChannels = 0;
            $deletedIds = [];
            $meilisearchIds = [];

            $this->line('Phase 1: Mise à jour et suppression des documents existants dans Meilisearch...');

            while (true) {
                $query = new \Meilisearch\Contracts\DocumentsQuery;
                $query->setLimit($batchSize);
                $query->setOffset($offset);
                $meilisearchDocs = $channelsIndex->getDocuments($query);

                if (empty($meilisearchDocs->getResults())) {
                    break;
                }

                $documentsToUpdate = [];

                foreach ($meilisearchDocs->getResults() as $meilisearchDoc) {
                    $channel = Channel::with('aliases')->find($meilisearchDoc['id']);

                    if (! $channel || ! $channel->shouldBeSearchable()) {
                        if (! $noDelete) {
                            $channelsIndex->deleteDocument($meilisearchDoc['id']);
                            $deletedChannels++;
                            $deletedIds[] = $meilisearchDoc['id'];
                        } else {
                            $meilisearchIds[] = (int) $meilisearchDoc['id'];
                        }

                        continue;
                    }

                    $meilisearchIds[] = (int) $meilisearchDoc['id'];
                    $documentsToUpdate[] = $channel->toSearchableArray();
                    $updatedChannels++;
                }

                if (! empty($documentsToUpdate)) {
                    $channelsIndex->updateDocuments($documentsToUpdate);
                }

                $this->line("Processed batch... Updated: {$updatedChannels} | Deleted: {$deletedChannels}");

                $offset += $batchSize;
                sleep(1);

                if ($offset % ($batchSize * 10) === 0) {
                    gc_collect_cycles();
                }
            }

            $this->newLine();
            $this->line('Phase 2: Ajout des channels manquants dans Meilisearch...');

            $dbIds = Channel::indexable()->pluck('id')->toArray();
            $idsToAdd = array_diff($dbIds, $meilisearchIds);

            if (! empty($idsToAdd)) {
                $this->line('Found '.count($idsToAdd).' channels to add...');

                $chunks = array_chunk($idsToAdd, $batchSize);
                foreach ($chunks as $chunkIndex => $chunk) {
                    $channelsToAdd = Channel::with('aliases')
                        ->whereIn('id', $chunk)
                        ->get();

                    $documentsToAdd = [];
                    foreach ($channelsToAdd as $channel) {
                        $documentsToAdd[] = $channel->toSearchableArray();
                        $addedChannels++;
                    }

                    if (! empty($documentsToAdd)) {
                        $channelsIndex->addDocuments($documentsToAdd);
                        $this->line('Added batch '.($chunkIndex + 1).'/'.count($chunks).': '.count($documentsToAdd).' channels');
                    }

                    sleep(1);
                }
            } else {
                $this->line('No missing channels to add.');
            }

            $this->newLine();
            $this->info("✓ Channels mis à jour : {$updatedChannels} | Supprimés : {$deletedChannels} | Ajoutés : {$addedChannels}");
            if (! empty($deletedIds)) {
                $this->info('IDs supprimés : '.implode(', ', $deletedIds));
            }
        } catch (\Exception $e) {
            $this->error('Error updating channels in Meilisearch: '.$e->getMessage());
            Log::error('Error updating channels in Meilisearch: '.$e->getMessage());
        }
    }

    private function updateVideos($videosIndex, bool $noDelete = false): void
    {
        try {
            $batchSize = 100;
            $offset = 0;
            $updatedVideos = 0;
            $addedVideos = 0;
            $deletedVideos = 0;
            $deletedIds = [];
            $meilisearchIds = [];

            $this->line('Phase 1: Mise à jour et suppression des documents existants dans Meilisearch...');

            while (true) {
                $query = new \Meilisearch\Contracts\DocumentsQuery;
                $query->setLimit($batchSize);
                $query->setOffset($offset);
                $meilisearchDocs = $videosIndex->getDocuments($query);

                if (empty($meilisearchDocs->getResults())) {
                    break;
                }

                $documentsToUpdate = [];

                foreach ($meilisearchDocs->getResults() as $meilisearchDoc) {
                    $video = Video::with([
                        'channel.aliases',
                        'tags.translations',
                        'translation',
                    ])->find($meilisearchDoc['id']);

                    if (! $video || ! $video->shouldBeSearchable()) {
                        if (! $noDelete) {
                            $videosIndex->deleteDocument($meilisearchDoc['id']);
                            $deletedVideos++;
                            $deletedIds[] = $meilisearchDoc['id'];
                        } else {
                            $meilisearchIds[] = (int) $meilisearchDoc['id'];
                        }

                        continue;
                    }

                    $meilisearchIds[] = (int) $meilisearchDoc['id'];
                    $documentsToUpdate[] = $video->toSearchableArray();
                    $updatedVideos++;
                }

                if (! empty($documentsToUpdate)) {
                    $videosIndex->updateDocuments($documentsToUpdate);
                }

                $this->line("Processed batch... Updated: {$updatedVideos} | Deleted: {$deletedVideos}");

                $offset += $batchSize;
                sleep(1);

                if ($offset % ($batchSize * 10) === 0) {
                    gc_collect_cycles();
                }
            }

            $this->newLine();
            $this->line('Phase 2: Ajout des vidéos manquantes dans Meilisearch...');

            $dbIds = Video::indexable()->pluck('id')->toArray();
            $idsToAdd = array_diff($dbIds, $meilisearchIds);

            if (! empty($idsToAdd)) {
                $this->line('Found '.count($idsToAdd).' videos to add...');

                $chunks = array_chunk($idsToAdd, $batchSize);
                foreach ($chunks as $chunkIndex => $chunk) {
                    $videosToAdd = Video::with([
                        'channel.aliases',
                        'tags.translations',
                        'translation',
                    ])
                        ->whereIn('id', $chunk)
                        ->get();

                    $documentsToAdd = [];
                    foreach ($videosToAdd as $video) {
                        $documentsToAdd[] = $video->toSearchableArray();
                        $addedVideos++;
                    }

                    if (! empty($documentsToAdd)) {
                        $videosIndex->addDocuments($documentsToAdd);
                        $this->line('Added batch '.($chunkIndex + 1).'/'.count($chunks).': '.count($documentsToAdd).' videos');
                    }

                    sleep(1);
                }
            } else {
                $this->line('No missing videos to add.');
            }

            $this->newLine();
            $this->info("✓ Videos mis à jour : {$updatedVideos} | Supprimés : {$deletedVideos} | Ajoutés : {$addedVideos}");
            if (! empty($deletedIds)) {
                $this->info('IDs supprimés : '.implode(', ', $deletedIds));
            }
        } catch (\Exception $e) {
            $this->error('Error updating videos in Meilisearch: '.$e->getMessage());
            Log::error('Error updating videos in Meilisearch: '.$e->getMessage());
        }
    }

    private function updateVideosOfChannel($videosIndex, int|string $channelId, bool $noDelete = false): void
    {
        try {
            $this->info("\n=== MISE À JOUR DES VIDÉOS DE LA CHAÎNE {$channelId} ===");

            $batchSize = 100;
            $updatedVideos = 0;
            $addedVideos = 0;
            $deletedVideos = 0;
            $deletedIds = [];

            $dbVideos = Video::where('channel_id', $channelId)
                ->indexable()
                ->with([
                    'channel.aliases',
                    'tags.translations',
                    'translation',
                ])
                ->get();

            $meilisearchIds = [];
            $searchOffset = 0;
            $searchLimit = 1000;

            while (true) {
                $searchResults = $videosIndex->search('', [
                    'filter' => ['channel.id = '.$channelId],
                    'limit' => $searchLimit,
                    'offset' => $searchOffset,
                ]);

                $hits = $searchResults->getHits();
                if (empty($hits)) {
                    break;
                }

                foreach ($hits as $hit) {
                    $meilisearchIds[] = $hit['id'];
                }

                if (count($hits) < $searchLimit) {
                    break;
                }

                $searchOffset += $searchLimit;
            }

            $dbIds = $dbVideos->pluck('id')->toArray();
            $addedVideos = count(array_diff($dbIds, $meilisearchIds));

            if (! $noDelete) {
                $idsToDelete = array_diff($meilisearchIds, $dbIds);
                if (! empty($idsToDelete)) {
                    $videosIndex->deleteDocuments(array_values($idsToDelete));
                    $deletedVideos = count($idsToDelete);
                    $deletedIds = array_values($idsToDelete);
                }
            }

            $documentsToUpdate = [];
            foreach ($dbVideos as $video) {
                $documentsToUpdate[] = $video->toSearchableArray();
                $updatedVideos++;
            }

            $chunks = array_chunk($documentsToUpdate, $batchSize);
            foreach ($chunks as $chunk) {
                $videosIndex->updateDocuments($chunk);
                $this->line('Processed batch... Updated: '.count($chunk).' videos');
                sleep(1);
            }

            $this->info("✓ Videos de la chaîne {$channelId} mis à jour : {$updatedVideos} | Supprimés : {$deletedVideos} | Ajoutés : {$addedVideos}");
            if (! empty($deletedIds)) {
                $this->info('IDs supprimés : '.implode(', ', $deletedIds));
            }
        } catch (\Exception $e) {
            $this->error('Error updating channel videos in Meilisearch: '.$e->getMessage());
            Log::error('Error updating channel videos in Meilisearch: '.$e->getMessage());
        }
    }
}
