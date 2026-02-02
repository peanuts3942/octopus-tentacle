<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Meilisearch\Client as MeilisearchClient;

class VideoServices
{
    private const DATA_TTL = 3600; // 1 hour

    public function __construct(
        private ?MeilisearchClient $meilisearch = null
    ) {
        $this->meilisearch = $meilisearch ?? app(MeilisearchClient::class);
    }

    /**
     * Get feed videos for a tentacle.
     */
    public function getFeedVideos(int $tentacleId, int $page, $request): LengthAwarePaginator|array
    {
        $perPage = 24;
        $cacheKey = "cache:data:allVideos:page:{$page}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            $data = unserialize($cachedData);

            return $this->formatFeedResponse($data, $request);
        }

        // Try Meilisearch first
        $data = $this->getFeedVideosFromMeilisearch($page, $perPage);

        // Fallback to SQL if Meilisearch failed
        if ($data === null) {
            $data = $this->getFeedVideosFromSql($page, $perPage);
        }

        Redis::setex($cacheKey, self::DATA_TTL, serialize($data));

        return $this->formatFeedResponse($data, $request);
    }

    private function getFeedVideosFromMeilisearch(int $page, int $perPage): ?array
    {
        try {
            $offset = ($page - 1) * $perPage;

            $result = $this->meilisearch->index('videos')->search('', [
                'filter' => ['is_published = true', 'draft = false'],
                'sort' => ['published_at:desc'],
                'limit' => $perPage,
                'offset' => $offset,
            ]);

            $hits = $result->getHits();
            $total = $result->getEstimatedTotalHits();

            // Explicit mapping for Blade compatibility
            $items = collect($hits)->map(fn ($hit) => $this->mapMeilisearchHitToObject($hit))->all();

            return [
                'source' => 'meilisearch',
                'items' => $items,
                'total' => $total,
                'perPage' => $perPage,
                'currentPage' => $page,
                'hasMore' => ($offset + count($hits)) < $total,
            ];
        } catch (\Exception $e) {
            Log::warning('Meilisearch query failed, falling back to SQL', [
                'error' => $e->getMessage(),
                'method' => 'getFeedVideos',
            ]);

            return null;
        }
    }

    /**
     * Map a Meilisearch hit to an object structure compatible with Blade views.
     */
    private function mapMeilisearchHitToObject(array $hit): object
    {
        return (object) [
            'id' => $hit['id'],
            'title' => $hit['title'],
            'slug' => $hit['slug'],
            'thumbnail_url' => $hit['thumbnail_url'],
            'preview_url' => $hit['preview_url'],
            'time' => $hit['time'],
            'published_at' => isset($hit['published_at']) ? \Carbon\Carbon::createFromTimestamp($hit['published_at']) : null,
            'is_published' => $hit['is_published'],
            'channel' => isset($hit['channel']) ? (object) [
                'id' => $hit['channel']['id'],
                'name' => $hit['channel']['name'],
                'slug' => $hit['channel']['slug'],
                'profile_picture_url' => $hit['channel']['profile_picture_url'] ?? null,
            ] : null,
        ];
    }

    private function getFeedVideosFromSql(int $page, int $perPage): array
    {
        $videos = Video::indexable()
            ->with(['channel.aliases', 'tags.translations', 'translation'])
            ->orderBy('videos.published_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform to match Meilisearch format (same structure as mapMeilisearchHitToObject)
        $items = $videos->map(fn ($video) => $this->mapVideoModelToObject($video))->toArray();

        return [
            'source' => 'sql',
            'items' => $items,
            'total' => $videos->total(),
            'perPage' => $perPage,
            'currentPage' => $page,
            'hasMore' => $videos->hasMorePages(),
        ];
    }

    /**
     * Map an Eloquent Video model to the same object structure as Meilisearch hits.
     */
    private function mapVideoModelToObject(Video $video): object
    {
        return (object) [
            'id' => $video->id,
            'title' => $video->translation?->title ?? 'Untitled',
            'slug' => $video->translation?->slug ?? $video->id,
            'thumbnail_url' => $video->thumbnail_url,
            'preview_url' => $video->preview_url,
            'time' => $video->time,
            'published_at' => $video->published_at,
            'is_published' => $video->is_published,
            'channel' => $video->channel ? (object) [
                'id' => $video->channel->id,
                'name' => $video->channel->name,
                'slug' => $video->channel->slug,
                'profile_picture_url' => $video->channel->profile_picture_url ?? null,
            ] : null,
        ];
    }

    private function formatFeedResponse(array $data, $request): LengthAwarePaginator|array
    {
        if ($request->ajax()) {
            return [
                'videos' => $data['items'],
                'hasMore' => $data['hasMore'],
                'currentPage' => $data['currentPage'],
                'total' => $data['total'],
            ];
        }

        return new LengthAwarePaginator(
            $data['items'],
            $data['total'],
            $data['perPage'],
            $data['currentPage'],
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Get related videos based on common tags.
     */
    public function getRelatedVideos(int $tentacleId, Video $video, int $page = 1): LengthAwarePaginator
    {
        $perPage = 24;
        $cacheKey = "cache:data:relatedVideos:{$video->id}:page:{$page}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            return unserialize($cachedData);
        }

        $tagIds = $video->tags->pluck('id')->toArray();

        if (empty($tagIds)) {
            return new LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $zone = config('app.zone');
        $tentacleId = config('app.tentacle_id');
        $nationalities = config("app.zone_{$zone}_nationalities", []);

        $relatedIds = DB::table('tag_video')
            ->select('tag_video.video_id')
            ->selectRaw('COUNT(*) as common_tags_count')
            ->join('videos', 'tag_video.video_id', '=', 'videos.id')
            ->join('channels', 'videos.channel_id', '=', 'channels.id')
            ->leftJoin('tentacle_video', function ($join) use ($tentacleId) {
                $join->on('videos.id', '=', 'tentacle_video.video_id')
                    ->where('tentacle_video.tentacle_id', '=', $tentacleId);
            })
            ->whereIn('tag_video.tag_id', $tagIds)
            ->where('tag_video.video_id', '!=', $video->id)
            ->where('videos.is_published', true)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('tentacle_video.id')
                        ->where('videos.draft', '!=', true);
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('tentacle_video.id')
                        ->where('tentacle_video.draft', '!=', true);
                });
            })
            ->whereJsonContains('videos.available_zones', $zone)
            ->whereIn('channels.nationality_iso', $nationalities)
            ->whereNull('videos.deleted_at')
            ->groupBy('tag_video.video_id')
            ->orderByDesc('common_tags_count')
            ->pluck('video_id')
            ->toArray();

        if (empty($relatedIds)) {
            return new LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $offset = ($page - 1) * $perPage;
        $currentPageIds = array_slice($relatedIds, $offset, $perPage);

        $videos = Video::whereIn('id', $currentPageIds)
            ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
            ->get()
            ->sortBy(fn ($v) => array_search($v->id, $currentPageIds));

        // Map to consistent object format
        $items = $videos->map(fn ($v) => $this->mapVideoModelToObject($v))->values();

        $result = new LengthAwarePaginator(
            $items,
            count($relatedIds),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        Redis::setex($cacheKey, self::DATA_TTL, serialize($result));

        return $result;
    }

    /**
     * Get a single video by ID with caching.
     */
    public function getVideo(int $id): ?Video
    {
        $cacheKey = "cache:data:video:{$id}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            return unserialize($cachedData);
        }

        $video = Video::with(['channel', 'tags', 'translation'])
            ->select('videos.*')
            ->where('videos.id', $id)
            ->where('videos.is_published', true)
            ->notDraftForTentacle()
            ->availableInZone()
            ->first();

        if ($video) {
            Redis::setex($cacheKey, self::DATA_TTL, serialize($video));
        }

        return $video;
    }
}
