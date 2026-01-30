<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TagController extends Controller
{
    private const DATA_TTL = 3600; // 1 hour

    private const VIEW_TTL = 300; // 5 minutes

    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $isAjax = $request->ajax();

        // View cache (only page 1, non-AJAX)
        $viewCacheKey = 'cache:view:page:pageCategories';
        if (! $isAjax && $page == 1) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        // Data cache
        $dataCacheKey = 'cache:data:allCategories';
        $cachedData = Redis::get($dataCacheKey);

        if ($cachedData) {
            $categories = unserialize($cachedData);
        } else {
            $tentacleId = config('app.tentacle_id');
            $categories = Tag::query()
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
                ->with('translation')
                ->get()
                ->sortBy('name');

            Redis::setex($dataCacheKey, self::DATA_TTL, serialize($categories));
        }

        $view = view('page.pageCategories', [
            'categories' => $categories,
            'h1' => 'All Categories',
            'h2' => 'Browse videos by category',
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
        $viewCacheKey = "cache:view:page:pageCategory:{$slug}";
        if (! $isAjax && $page == 1) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        // Data cache for category
        $categoryCacheKey = "cache:data:category:{$slug}";
        $cachedCategory = Redis::get($categoryCacheKey);

        if ($cachedCategory) {
            $category = unserialize($cachedCategory);
        } else {
            $category = Tag::with('translation')
                ->whereHas('translation', fn ($q) => $q->where('slug', $slug))
                ->first();

            if (! $category) {
                abort(404);
            }

            Redis::setex($categoryCacheKey, self::DATA_TTL, serialize($category));
        }

        if (! $category) {
            abort(404);
        }

        // Data cache for videos
        $videosCacheKey = "cache:data:category:{$category->id}:videos:page:{$page}";
        $cachedVideos = Redis::get($videosCacheKey);

        if ($cachedVideos) {
            $videos = unserialize($cachedVideos);
        } else {
            $videos = Video::query()
                ->select('videos.*')
                ->whereHas('tags', fn ($q) => $q->where('tags.id', $category->id))
                ->where('videos.is_published', true)
                ->notDraftForTentacle()
                ->availableInZone()
                ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
                ->orderBy('videos.published_at', 'desc')
                ->paginate(24);

            Redis::setex($videosCacheKey, self::DATA_TTL, serialize($videos));
        }

        $view = view('page.pageCategory', [
            'category' => $category,
            'videos' => $videos,
        ])->render();

        if (! $isAjax && $page == 1) {
            Redis::setex($viewCacheKey, self::VIEW_TTL, $view);
        }

        return response($view);
    }
}
