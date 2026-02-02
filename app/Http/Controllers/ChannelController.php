<?php

namespace App\Http\Controllers;

use App\Services\ChannelServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ChannelController extends Controller
{
    private const VIEW_TTL = 300; // 5 minutes

    public function __construct(
        private ChannelServices $channelServices
    ) {}

    public function index(Request $request)
    {
        // View cache (non-AJAX only)
        $viewCacheKey = 'cache:view:page:pageModels';
        if (! $request->ajax()) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        // Get channels via service (Meilisearch + SQL fallback, with data cache)
        $channels = $this->channelServices->getAllChannels();

        // For AJAX requests, return JSON directly
        if ($request->ajax()) {
            return response()->json($channels);
        }

        $view = view('page.pageModels', [
            'channels' => $channels,
            'h1' => 'All Models',
            'h2' => 'Browse videos by model',
        ])->render();

        Redis::setex($viewCacheKey, self::VIEW_TTL, $view);

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

        // Get channel via service (with data cache)
        $channel = $this->channelServices->getChannel($slug);

        if (! $channel) {
            abort(404);
        }

        // Get channel videos via service (Meilisearch + SQL fallback, with data cache)
        $videos = $this->channelServices->getChannelVideos($channel->id, $page, $request);

        // For AJAX requests, return JSON directly
        if ($isAjax) {
            return response()->json([
                'channel' => $channel,
                'videos' => $videos,
            ]);
        }

        $view = view('page.pageModel', [
            'channel' => $channel,
            'videos' => $videos,
        ])->render();

        if ($page == 1) {
            Redis::setex($viewCacheKey, self::VIEW_TTL, $view);
        }

        return response($view);
    }
}
