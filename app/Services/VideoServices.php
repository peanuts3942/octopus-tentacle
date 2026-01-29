<?php

namespace App\Services;

use App\Models\Tentacle;
use App\Models\Video;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Meilisearch\Client as MeilisearchClient;

class VideoServices
{
    /**
     * Get feed videos for a tentacle with draft override.
     *
     * Videos are excluded if:
     * - tentacle_videos.draft = 1 (explicit draft for this tentacle)
     * - videos.draft = 1 AND no tentacle_videos override exists
     */
    public function getFeedVideos(int $tentacleId, int $page, $request): LengthAwarePaginator|array
    {
        $perPage = 24;

        $videos = Video::query()
            ->select([
                'videos.id',
                'videos.title',
                'videos.slug',
                'videos.thumbnail_url',
                'videos.preview_url',
                'videos.published_at',
                'videos.channel_id',
                'videos.time',
                'videos.draft as video_draft',
            ])
            ->leftJoin('tentacle_videos', function ($join) use ($tentacleId) {
                $join->on('videos.id', '=', 'tentacle_videos.video_id')
                    ->where('tentacle_videos.tentacle_id', '=', $tentacleId);
            })
            ->where(function ($query) {
                // Exclude if tentacle_videos.draft = 1
                // OR if videos.draft = 1 AND no tentacle_videos override exists
                $query->where(function ($q) {
                    $q->whereNull('tentacle_videos.id')
                        ->where('videos.draft', '!=', true);
                })->orWhere(function ($q) {
                    $q->whereNotNull('tentacle_videos.id')
                        ->where('tentacle_videos.draft', '!=', true);
                });
            })
            ->where('videos.is_published', true)
            ->with(['channel:id,name,slug,profile_picture_url'])
            ->orderBy('videos.published_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($request->ajax()) {
            return [
                'videos' => $videos->items(),
                'hasMore' => $videos->hasMorePages(),
                'currentPage' => $videos->currentPage(),
                'total' => $videos->total(),
            ];
        }

        return $videos;
    }

    /**
     * Get related videos using hybrid SQL + Redis + MeiliSearch approach.
     * Applies draft override for the specified tentacle.
     */
    public function getRelatedVideos(int $tentacleId, Video $video, int $page = 1): LengthAwarePaginator
    {
        $startTime = microtime(true);
        $videoId = $video->id;

        $perPage = config('videos.related_per_page', 24);
        $cachePages = config('videos.related_cache_pages', 10);
        $cacheTtl = config('videos.related_cache_ttl', 86400);
        $totalIdsToCache = $perPage * $cachePages;

        Log::info('[RelatedVideos] Starting related videos lookup', [
            'tentacle_id' => $tentacleId,
            'video_id' => $videoId,
            'page' => $page,
        ]);

        // Step 1: Try to get IDs from Redis cache (tentacle-specific)
        $cacheKey = "related_video_ids:{$tentacleId}:{$videoId}";
        $allRelatedIds = Cache::get($cacheKey);

        if ($allRelatedIds) {
            Log::info('[RelatedVideos] Redis cache HIT', [
                'video_id' => $videoId,
                'cached_ids_count' => count($allRelatedIds),
            ]);
        } else {
            // Step 2: Cache MISS - Execute SQL query with draft override
            Log::info('[RelatedVideos] Redis cache MISS - executing SQL query');

            $tagIds = $video->tags->pluck('id')->toArray();

            if (empty($tagIds)) {
                Log::warning('[RelatedVideos] Video has no tags');

                return new LengthAwarePaginator(
                    collect([]),
                    0,
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            }

            // SQL query with draft override logic
            $allRelatedIds = DB::table('tag_video')
                ->select('tag_video.video_id')
                ->selectRaw('COUNT(*) as common_tags_count')
                ->join('videos', 'tag_video.video_id', '=', 'videos.id')
                ->leftJoin('tentacle_videos', function ($join) use ($tentacleId) {
                    $join->on('videos.id', '=', 'tentacle_videos.video_id')
                        ->where('tentacle_videos.tentacle_id', '=', $tentacleId);
                })
                ->whereIn('tag_video.tag_id', $tagIds)
                ->where('tag_video.video_id', '!=', $videoId)
                ->where('videos.is_published', true)
                ->where(function ($query) {
                    // Draft override logic
                    $query->where(function ($q) {
                        $q->whereNull('tentacle_videos.id')
                            ->where('videos.draft', '!=', true);
                    })->orWhere(function ($q) {
                        $q->whereNotNull('tentacle_videos.id')
                            ->where('tentacle_videos.draft', '!=', true);
                    });
                })
                ->groupBy('tag_video.video_id')
                ->orderByDesc('common_tags_count')
                ->limit($totalIdsToCache)
                ->pluck('video_id')
                ->toArray();

            Cache::put($cacheKey, $allRelatedIds, $cacheTtl);

            Log::info('[RelatedVideos] IDs cached in Redis', [
                'found_ids_count' => count($allRelatedIds),
            ]);
        }

        // Step 3: Paginate the IDs
        $offset = ($page - 1) * $perPage;
        $currentPageIds = array_slice($allRelatedIds, $offset, $perPage);

        if (empty($currentPageIds)) {
            return new LengthAwarePaginator(
                collect([]),
                count($allRelatedIds),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Step 4: Fetch metadata from MeiliSearch
        $videosFromMeili = $this->fetchVideosFromMeilisearch($currentPageIds);
        $meiliFoundIds = $videosFromMeili->pluck('id')->toArray();
        $meiliMissingIds = array_diff($currentPageIds, $meiliFoundIds);

        // Step 5: Fallback to SQL for missing videos
        $videosFromSql = collect([]);
        if (! empty($meiliMissingIds)) {
            $videosFromSql = Video::whereIn('id', $meiliMissingIds)
                ->published()
                ->with(['channel', 'tags'])
                ->get()
                ->map(fn ($video) => $this->formatVideoData($video));
        }

        // Step 6: Merge results and maintain order
        $allVideos = $videosFromMeili->merge($videosFromSql);
        $orderedVideos = collect($currentPageIds)
            ->map(fn ($id) => $allVideos->firstWhere('id', $id))
            ->filter();

        $totalTime = microtime(true) - $startTime;

        Log::info('[RelatedVideos] Request completed', [
            'returned_count' => $orderedVideos->count(),
            'total_time_ms' => round($totalTime * 1000, 2),
        ]);

        return new LengthAwarePaginator(
            $orderedVideos,
            count($allRelatedIds),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Fetch videos from MeiliSearch by IDs.
     */
    private function fetchVideosFromMeilisearch(array $ids): Collection
    {
        if (empty($ids)) {
            return collect([]);
        }

        try {
            $client = new MeilisearchClient(
                config('scout.meilisearch.host'),
                config('scout.meilisearch.key')
            );

            $index = $client->index('videos');

            $results = $index->search('', [
                'filter' => 'id IN ['.implode(',', $ids).']',
                'limit' => count($ids),
            ]);

            return collect($results->getHits())->map(fn ($doc) => $this->formatMeilisearchDocument($doc));

        } catch (\Exception $e) {
            Log::error('[RelatedVideos] MeiliSearch error', [
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Format MeiliSearch document to standardized object.
     */
    private function formatMeilisearchDocument(array $doc): object
    {
        return (object) [
            'id' => $doc['id'],
            'title' => $doc['title'] ?? 'Untitled',
            'slug' => $doc['slug'] ?? '',
            'thumbnail_url' => $doc['thumbnail_url'] ?? null,
            'preview_url' => $doc['preview_url'] ?? null,
            'time' => $doc['time'] ?? 0,
            'published_at' => isset($doc['published_at'])
                ? (object) ['timestamp' => $doc['published_at']]
                : null,
            'channel' => (object) [
                'id' => $doc['channel']['id'] ?? null,
                'name' => $doc['channel']['name'] ?? 'Unknown',
                'slug' => $doc['channel']['slug'] ?? '',
                'profile_picture_url' => $doc['channel']['profile_picture_url'] ?? null,
            ],
        ];
    }

    /**
     * Format Video model to standardized object.
     */
    private function formatVideoData(Video $video): object
    {
        return (object) [
            'id' => $video->id,
            'title' => $video->title,
            'slug' => $video->slug,
            'thumbnail_url' => $video->thumbnail_url,
            'preview_url' => $video->preview_url,
            'time' => $video->time,
            'published_at' => $video->published_at,
            'channel' => $video->channel,
        ];
    }
}
