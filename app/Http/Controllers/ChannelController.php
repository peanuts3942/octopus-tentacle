<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ChannelController extends Controller
{
    private const DATA_TTL = 3600; // 1 hour

    private const VIEW_TTL = 300; // 5 minutes

    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $isAjax = $request->ajax();

        // View cache (only page 1, non-AJAX)
        $viewCacheKey = 'cache:view:page:pageModels';
        if (! $isAjax && $page == 1) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        // Data cache
        $dataCacheKey = 'cache:data:allModels';
        $cachedData = Redis::get($dataCacheKey);

        if ($cachedData) {
            $channels = unserialize($cachedData);
        } else {
            $tentacleId = config('app.tentacle_id');
            $channels = Channel::query()
                ->whereHas('videos', function ($q) use ($tentacleId) {
                    $q->where('is_published', true)
                        ->availableInZone()
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
                ->orderBy('name')
                ->get();

            Redis::setex($dataCacheKey, self::DATA_TTL, serialize($channels));
        }

        $view = view('page.pageModels', [
            'channels' => $channels,
            'h1' => 'All Models',
            'h2' => 'Browse videos by model',
        ])->render();

        if (! $isAjax && $page == 1) {
            Redis::setex($viewCacheKey, self::VIEW_TTL, $view);
        }

        return response($view);
    }

    public function show(Request $request, string $slug)
    {
        $page = $request->get('page', 1);
        $isAjax = $request->ajax();

        // View cache (only page 1, non-AJAX)
        $viewCacheKey = "cache:view:page:pageModel:{$slug}";
        if (! $isAjax && $page == 1) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        // Data cache for channel
        $channelCacheKey = "cache:data:model:{$slug}";
        $cachedChannel = Redis::get($channelCacheKey);

        if ($cachedChannel) {
            $channel = unserialize($cachedChannel);
        } else {
            $channel = Channel::with('translations')->where('slug', $slug)->first();

            if (! $channel) {
                abort(404);
            }

            Redis::setex($channelCacheKey, self::DATA_TTL, serialize($channel));
        }

        if (! $channel) {
            abort(404);
        }

        // Data cache for videos
        $videosCacheKey = "cache:data:model:{$channel->id}:videos:page:{$page}";
        $cachedVideos = Redis::get($videosCacheKey);

        if ($cachedVideos) {
            $videos = unserialize($cachedVideos);
        } else {
            $videos = Video::query()
                ->select('videos.*')
                ->where('videos.channel_id', $channel->id)
                ->where('videos.is_published', true)
                ->notDraftForTentacle()
                ->availableInZone()
                ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
                ->orderBy('videos.published_at', 'desc')
                ->paginate(24);

            Redis::setex($videosCacheKey, self::DATA_TTL, serialize($videos));
        }

        $view = view('page.pageModel', [
            'channel' => $channel,
            'videos' => $videos,
        ])->render();

        if (! $isAjax && $page == 1) {
            Redis::setex($viewCacheKey, self::VIEW_TTL, $view);
        }

        return response($view);
    }
}
