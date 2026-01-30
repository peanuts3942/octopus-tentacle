<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class VideoServices
{
    private const DATA_TTL = 3600; // 1 hour

    /**
     * Get feed videos for a tentacle.
     */
    public function getFeedVideos(int $tentacleId, int $page, $request): LengthAwarePaginator|array
    {
        $perPage = 24;
        $cacheKey = "cache:data:allVideos:page:{$page}";

        $cachedData = Redis::get($cacheKey);

        if ($cachedData) {
            $videos = unserialize($cachedData);
        } else {
            $videos = Video::query()
                ->select('videos.*')
                ->where('videos.is_published', true)
                ->notDraftForTentacle()
                ->availableInZone()
                ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
                ->orderBy('videos.published_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            Redis::setex($cacheKey, self::DATA_TTL, serialize($videos));
        }

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

        $relatedIds = DB::table('tag_video')
            ->select('tag_video.video_id')
            ->selectRaw('COUNT(*) as common_tags_count')
            ->join('videos', 'tag_video.video_id', '=', 'videos.id')
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

        $result = new LengthAwarePaginator(
            $videos,
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
