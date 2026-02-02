<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Meilisearch\Client as MeilisearchClient;

class ChannelServices
{
    private const DATA_TTL = 3600; // 1 hour

    public function __construct(
        private ?MeilisearchClient $meilisearch = null
    ) {
        $this->meilisearch = $meilisearch ?? app(MeilisearchClient::class);
    }

    /**
     * Get all channels.
     */
    public function getAllChannels(): array
    {
        $cacheKey = 'cache:data:allChannels';

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            return unserialize($cachedData);
        }

        // Try Meilisearch first
        $items = $this->getAllChannelsFromMeilisearch();

        // Fallback to SQL if Meilisearch failed
        if ($items === null) {
            $items = $this->getAllChannelsFromSql();
        }

        Redis::setex($cacheKey, self::DATA_TTL, serialize($items));

        return $items;
    }

    private function getAllChannelsFromMeilisearch(): ?array
    {
        try {
            // Get all channels (limit high enough for all)
            $result = $this->meilisearch->index('channels')->search('', [
                'sort' => ['name:asc'],
                'limit' => 10000,
            ]);

            $hits = $result->getHits();

            return collect($hits)->map(fn ($hit) => $this->mapMeilisearchChannelToObject($hit))->all();

            // Pagination commented out for future use:
            // $offset = ($page - 1) * $perPage;
            // $result = $this->meilisearch->index('channels')->search('', [
            //     'sort' => ['name:asc'],
            //     'limit' => $perPage,
            //     'offset' => $offset,
            // ]);
            // $total = $result->getEstimatedTotalHits();
            // return [
            //     'source' => 'meilisearch',
            //     'items' => $items,
            //     'total' => $total,
            //     'perPage' => $perPage,
            //     'currentPage' => $page,
            //     'hasMore' => ($offset + count($hits)) < $total,
            // ];
        } catch (\Exception $e) {
            Log::warning('Meilisearch query failed, falling back to SQL', [
                'error' => $e->getMessage(),
                'method' => 'getAllChannels',
            ]);

            return null;
        }
    }

    private function getAllChannelsFromSql(): array
    {
        $channels = Channel::indexable()
            ->with('aliases')
            ->orderBy('name', 'asc')
            ->get();

        return $channels->map(fn ($channel) => $this->mapChannelModelToObject($channel))->toArray();

        // Pagination commented out for future use:
        // $channels = Channel::indexable()
        //     ->with('aliases')
        //     ->orderBy('name', 'asc')
        //     ->paginate($perPage, ['*'], 'page', $page);
        // return [
        //     'source' => 'sql',
        //     'items' => $items,
        //     'total' => $channels->total(),
        //     'perPage' => $perPage,
        //     'currentPage' => $page,
        //     'hasMore' => $channels->hasMorePages(),
        // ];
    }

    /**
     * Get a single channel by slug.
     */
    public function getChannel(string $slug): ?object
    {
        $cacheKey = "cache:data:channel:{$slug}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            return unserialize($cachedData);
        }

        $zone = config('app.zone');

        $channel = Channel::with(['translations' => fn ($q) => $q->where('locale', $zone)])
            ->where('slug', $slug)
            ->first();

        if (! $channel) {
            return null;
        }

        $translation = $channel->translations->first();

        $result = (object) [
            'id' => $channel->id,
            'name' => $channel->name,
            'slug' => $channel->slug,
            'profile_picture_url' => $channel->profile_picture_url ?? null,
            'short_description' => $translation?->short_description ?? null,
        ];

        Redis::setex($cacheKey, self::DATA_TTL, serialize($result));

        return $result;
    }

    /**
     * Get videos for a channel.
     */
    public function getChannelVideos(int $channelId, int $page, $request): LengthAwarePaginator|array
    {
        $perPage = 24;
        $cacheKey = "cache:data:channel:{$channelId}:videos:page:{$page}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            $data = unserialize($cachedData);

            return $this->formatResponse($data, $request);
        }

        // Try Meilisearch first
        $data = $this->getChannelVideosFromMeilisearch($channelId, $page, $perPage);

        // Fallback to SQL if Meilisearch failed
        if ($data === null) {
            $data = $this->getChannelVideosFromSql($channelId, $page, $perPage);
        }

        Redis::setex($cacheKey, self::DATA_TTL, serialize($data));

        return $this->formatResponse($data, $request);
    }

    private function getChannelVideosFromMeilisearch(int $channelId, int $page, int $perPage): ?array
    {
        try {
            $offset = ($page - 1) * $perPage;

            $result = $this->meilisearch->index('videos')->search('', [
                'filter' => ["channel.id = {$channelId}"],
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
                'method' => 'getChannelVideos',
            ]);

            return null;
        }
    }

    private function getChannelVideosFromSql(int $channelId, int $page, int $perPage): array
    {
        $videos = Video::indexable()
            ->where('videos.channel_id', $channelId)
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
     * Map a Meilisearch channel hit to object.
     */
    private function mapMeilisearchChannelToObject(array $hit): object
    {
        return (object) [
            'id' => $hit['id'],
            'name' => $hit['name'],
            'slug' => $hit['slug'],
            'profile_picture_url' => $hit['profile_picture_url'] ?? null,
            'card_picture_url' => $hit['card_picture_url'] ?? null,
            'nationality_iso' => $hit['nationality_iso'] ?? null,
            'aliases' => $hit['aliases'] ?? [],
        ];
    }

    /**
     * Map an Eloquent Channel model to object.
     */
    private function mapChannelModelToObject(Channel $channel): object
    {
        return (object) [
            'id' => $channel->id,
            'name' => $channel->name,
            'slug' => $channel->slug,
            'profile_picture_url' => $channel->profile_picture_url ?? null,
            'card_picture_url' => $channel->card_picture_url ?? null,
            'nationality_iso' => $channel->nationality_iso ?? null,
            'aliases' => $channel->aliases->pluck('alias')->toArray(),
        ];
    }

    /**
     * Map a Meilisearch video hit to object (same as VideoServices).
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
     * Map an Eloquent Video model to object (same as VideoServices).
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
