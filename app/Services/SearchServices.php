<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Tag;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Meilisearch\Client as MeilisearchClient;
use Meilisearch\Contracts\SearchQuery;

class SearchServices
{
    public function __construct(
        private ?MeilisearchClient $meilisearch = null
    ) {
        $this->meilisearch = $meilisearch ?? app(MeilisearchClient::class);
    }

    /**
     * Search videos, channels, and tags.
     *
     * @return array{videos: array, channels: array, tags: array, totalVideos: int, hasMoreVideos: bool}
     */
    public function search(string $query, int $page = 1): array
    {
        $perPage = 24;

        // Try Meilisearch first
        $result = $this->searchFromMeilisearch($query, $page, $perPage);

        // Fallback to SQL if Meilisearch failed
        if ($result === null) {
            $result = $this->searchFromSql($query, $page, $perPage);
        }

        return $result;
    }

    private function searchFromMeilisearch(string $query, int $page, int $perPage): ?array
    {
        try {
            $offset = ($page - 1) * $perPage;

            // MultiSearch: videos (paginated), channels (all), tags (all)
            $results = $this->meilisearch->multiSearch([
                (new SearchQuery)
                    ->setIndexUid('videos')
                    ->setQuery($query)
                    ->setLimit($perPage)
                    ->setOffset($offset)
                    ->setSort(['published_at:desc']),
                (new SearchQuery)
                    ->setIndexUid('channels')
                    ->setQuery($query)
                    ->setLimit(1000)
                    ->setSort(['name:asc']),
                (new SearchQuery)
                    ->setIndexUid('tags')
                    ->setQuery($query)
                    ->setLimit(1000)
                    ->setSort(['name:asc']),
            ]);

            $videosResult = $results['results'][0];
            $channelsResult = $results['results'][1];
            $tagsResult = $results['results'][2];

            $videos = collect($videosResult['hits'])->map(fn ($hit) => $this->mapMeilisearchVideoToObject($hit))->all();
            $channels = collect($channelsResult['hits'])->map(fn ($hit) => $this->mapMeilisearchChannelToObject($hit))->all();
            $tags = collect($tagsResult['hits'])->map(fn ($hit) => $this->mapMeilisearchTagToObject($hit))->all();

            $totalVideos = $videosResult['estimatedTotalHits'] ?? 0;

            return [
                'source' => 'meilisearch',
                'videos' => $videos,
                'channels' => $channels,
                'tags' => $tags,
                'totalVideos' => $totalVideos,
                'currentPage' => $page,
                'perPage' => $perPage,
                'hasMoreVideos' => ($offset + count($videos)) < $totalVideos,
            ];
        } catch (\Exception $e) {
            Log::warning('Meilisearch search failed, falling back to SQL', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }

    private function searchFromSql(string $query, int $page, int $perPage): array
    {
        $zone = config('app.zone');
        $tentacleId = config('app.tentacle_id');
        $nationalities = config("app.zone_{$zone}_nationalities", []);

        // Search videos (with channel aliases)
        $videosQuery = Video::query()
            ->select('videos.*')
            ->where('videos.is_published', true)
            ->whereNull('videos.deleted_at')
            ->whereJsonContains('videos.available_zones', $zone)
            ->leftJoin('tentacle_video', function ($join) use ($tentacleId) {
                $join->on('videos.id', '=', 'tentacle_video.video_id')
                    ->where('tentacle_video.tentacle_id', '=', $tentacleId);
            })
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('tentacle_video.id')
                        ->where('videos.draft', '!=', true);
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('tentacle_video.id')
                        ->where('tentacle_video.draft', '!=', true);
                });
            })
            ->whereHas('channel', fn ($c) => $c->whereIn('nationality_iso', $nationalities))
            ->where(function ($q) use ($query) {
                $q->whereHas('translation', fn ($t) => $t->where('title', 'LIKE', "%{$query}%"))
                    ->orWhereHas('channel', fn ($c) => $c->where('name', 'LIKE', "%{$query}%"))
                    ->orWhereHas('channel.aliases', fn ($a) => $a->where('alias', 'LIKE', "%{$query}%"));
            })
            ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
            ->orderBy('videos.published_at', 'desc');

        $totalVideos = $videosQuery->count();
        $videos = $videosQuery->skip(($page - 1) * $perPage)->take($perPage)->get();
        $videoItems = $videos->map(fn ($video) => $this->mapVideoModelToObject($video))->toArray();

        // Search channels (with aliases)
        $channels = Channel::query()
            ->whereIn('nationality_iso', $nationalities)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhereHas('aliases', fn ($a) => $a->where('alias', 'LIKE', "%{$query}%"));
            })
            ->with('aliases')
            ->orderBy('name', 'asc')
            ->get();
        $channelItems = $channels->map(fn ($channel) => $this->mapChannelModelToObject($channel))->toArray();

        // Search tags
        $tags = Tag::query()
            ->whereHas('translations', fn ($t) => $t->where('locale', $zone)->where('name', 'LIKE', "%{$query}%"))
            ->with(['translations' => fn ($q) => $q->where('locale', $zone)])
            ->get()
            ->sortBy(fn ($tag) => $tag->translations->first()?->name ?? 'zzz');
        $tagItems = $tags->map(fn ($tag) => $this->mapTagModelToObject($tag))->values()->toArray();

        return [
            'source' => 'sql',
            'videos' => $videoItems,
            'channels' => $channelItems,
            'tags' => $tagItems,
            'totalVideos' => $totalVideos,
            'currentPage' => $page,
            'perPage' => $perPage,
            'hasMoreVideos' => ($page * $perPage) < $totalVideos,
        ];
    }

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

    private function mapMeilisearchChannelToObject(array $hit): object
    {
        return (object) [
            'id' => $hit['id'],
            'name' => $hit['name'],
            'slug' => $hit['slug'],
            'profile_picture_url' => $hit['profile_picture_url'] ?? null,
            'card_picture_url' => $hit['card_picture_url'] ?? null,
        ];
    }

    private function mapMeilisearchTagToObject(array $hit): object
    {
        return (object) [
            'id' => $hit['id'],
            'name' => $hit['name'] ?? 'Unnamed',
            'slug' => $hit['slug'] ?? $hit['id'],
        ];
    }

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

    private function mapChannelModelToObject(Channel $channel): object
    {
        return (object) [
            'id' => $channel->id,
            'name' => $channel->name,
            'slug' => $channel->slug,
            'profile_picture_url' => $channel->profile_picture_url ?? null,
            'card_picture_url' => $channel->card_picture_url ?? null,
        ];
    }

    private function mapTagModelToObject(Tag $tag): object
    {
        $translation = $tag->translations->first();

        return (object) [
            'id' => $tag->id,
            'name' => $translation?->name ?? 'Unnamed',
            'slug' => $translation?->slug ?? $tag->id,
        ];
    }
}
