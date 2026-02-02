<?php

namespace App\Http\Controllers;

use App\Services\TagServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TagController extends Controller
{
    private const VIEW_TTL = 300; // 5 minutes

    public function __construct(
        private TagServices $tagServices
    ) {}

    public function index(Request $request)
    {
        // View cache (non-AJAX only)
        $viewCacheKey = 'cache:view:page:pageCategories';
        if (! $request->ajax()) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        // Get tags via service (Meilisearch + SQL fallback, with data cache)
        $categories = $this->tagServices->getAllTags();

        // For AJAX requests, return JSON directly
        if ($request->ajax()) {
            return response()->json($categories);
        }

        $view = view('page.pageCategories', [
            'categories' => $categories,
            'h1' => 'All Categories',
            'h2' => 'Browse videos by category',
        ])->render();

        Redis::setex($viewCacheKey, self::VIEW_TTL, $view);

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

        // Get tag via service (with data cache)
        $category = $this->tagServices->getTag($slug);

        if (! $category) {
            abort(404);
        }

        // Get tag videos via service (Meilisearch + SQL fallback, with data cache)
        $videos = $this->tagServices->getTagVideos($category->id, $page, $request);

        // For AJAX requests, return JSON directly
        if ($isAjax) {
            return response()->json([
                'category' => $category,
                'videos' => $videos,
            ]);
        }

        $view = view('page.pageCategory', [
            'category' => $category,
            'videos' => $videos,
        ])->render();

        if ($page == 1) {
            Redis::setex($viewCacheKey, self::VIEW_TTL, $view);
        }

        return response($view);
    }
}
