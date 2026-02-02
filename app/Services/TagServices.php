<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Video;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Meilisearch\Client as MeilisearchClient;

class TagServices
{
    private const DATA_TTL = 3600; // 1 hour

    public function __construct(
        private ?MeilisearchClient $meilisearch = null
    ) {
        $this->meilisearch = $meilisearch ?? app(MeilisearchClient::class);
    }

    /**
     * Get all tags.
     */
    public function getAllTags(): array
    {
        $cacheKey = 'cache:data:allTags';

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            return unserialize($cachedData);
        }

        // Try Meilisearch first
        $items = $this->getAllTagsFromMeilisearch();

        // Fallback to SQL if Meilisearch failed
        if ($items === null) {
            $items = $this->getAllTagsFromSql();
        }

        Redis::setex($cacheKey, self::DATA_TTL, serialize($items));

        return $items;
    }

    private function getAllTagsFromMeilisearch(): ?array
    {
        try {
            $result = $this->meilisearch->index('tags')->search('', [
                'sort' => ['name:asc'],
                'limit' => 10000,
            ]);

            $hits = $result->getHits();

            return collect($hits)->map(fn ($hit) => $this->mapMeilisearchTagToObject($hit))->all();
        } catch (\Exception $e) {
            Log::warning('Meilisearch query failed, falling back to SQL', [
                'error' => $e->getMessage(),
                'method' => 'getAllTags',
            ]);

            return null;
        }
    }

    private function getAllTagsFromSql(): array
    {
        $zone = config('app.zone');
        $tentacleId = config('app.tentacle_id');
        $nationalities = config("app.zone_{$zone}_nationalities", []);

        $tags = Tag::query()
            ->whereHas('videos', function ($q) use ($zone, $tentacleId, $nationalities) {
                $q->where('is_published', true)
                    ->whereNull('deleted_at')
                    ->whereJsonContains('available_zones', $zone)
                    ->whereHas('channel', fn ($c) => $c->whereIn('nationality_iso', $nationalities))
                    ->where(function ($query) use ($tentacleId) {
                        $query->where(function ($inner) use ($tentacleId) {
                            $inner->whereDoesntHave('tentacleVideos', fn ($tv) => $tv->where('tentacle_id', $tentacleId))
                                ->where('draft', '!=', true);
                        })->orWhereHas('tentacleVideos', fn ($tv) => $tv
                            ->where('tentacle_id', $tentacleId)
                            ->where('draft', '!=', true)
                        );
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $zone)])
            ->get()
            ->sortBy(fn ($tag) => $tag->translations->first()?->name ?? 'zzz');

        return $tags->map(fn ($tag) => $this->mapTagModelToObject($tag))->values()->toArray();
    }

    /**
     * Get a single tag by slug.
     */
    public function getTag(string $slug): ?object
    {
        $cacheKey = "cache:data:tag:{$slug}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            return unserialize($cachedData);
        }

        $zone = config('app.zone');

        $tag = Tag::with(['translations' => fn ($q) => $q->where('locale', $zone)])
            ->whereHas('translations', fn ($q) => $q->where('locale', $zone)->where('slug', $slug))
            ->first();

        if (! $tag) {
            return null;
        }

        $result = $this->mapTagModelToObject($tag);

        Redis::setex($cacheKey, self::DATA_TTL, serialize($result));

        return $result;
    }

    /**
     * Get videos for a tag.
     */
    public function getTagVideos(int $tagId, int $page, $request): LengthAwarePaginator|array
    {
        $perPage = 24;
        $cacheKey = "cache:data:tag:{$tagId}:videos:page:{$page}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            $data = unserialize($cachedData);

            return $this->formatResponse($data, $request);
        }

        // Try Meilisearch first
        $data = $this->getTagVideosFromMeilisearch($tagId, $page, $perPage);

        // Fallback to SQL if Meilisearch failed
        if ($data === null) {
            $data = $this->getTagVideosFromSql($tagId, $page, $perPage);
        }

        Redis::setex($cacheKey, self::DATA_TTL, serialize($data));

        return $this->formatResponse($data, $request);
    }

    private function getTagVideosFromMeilisearch(int $tagId, int $page, int $perPage): ?array
    {
        try {
            $offset = ($page - 1) * $perPage;

            $result = $this->meilisearch->index('videos')->search('', [
                'filter' => ["tags.id = {$tagId}"],
                'sort' => ['published_at:desc'],
                'limit' => $perPage,
                'offset' => $offset,
            ]);

            $hits = $result->getHits();
            $total = $result->getEstimatedTotalHits();

            $items = collect($hits)->map(fn ($hit) => $this->mapMeilisearchVideoToObject($hit))->all();

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
                'method' => 'getTagVideos',
            ]);

            return null;
        }
    }

    private function getTagVideosFromSql(int $tagId, int $page, int $perPage): array
    {
        $videos = Video::indexable()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId))
            ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
            ->orderBy('videos.published_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

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
     * Map a Meilisearch tag hit to object.
     */
    private function mapMeilisearchTagToObject(array $hit): object
    {
        return (object) [
            'id' => $hit['id'],
            'name' => $hit['name'] ?? 'Unnamed',
            'slug' => $hit['slug'] ?? $hit['id'],
        ];
    }

    /**
     * Map an Eloquent Tag model to object.
     */
    private function mapTagModelToObject(Tag $tag): object
    {
        $translation = $tag->translations->first();

        return (object) [
            'id' => $tag->id,
            'name' => $translation?->name ?? 'Unnamed',
            'slug' => $translation?->slug ?? $tag->id,
        ];
    }

    /**
     * Map a Meilisearch video hit to object.
     */
    private function mapMeilisearchVideoToObject(array $hit): object
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

    /**
     * Map an Eloquent Video model to object.
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

    private function formatResponse(array $data, $request): LengthAwarePaginator|array
    {
        if ($request->ajax()) {
            return [
                'items' => $data['items'],
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
}
