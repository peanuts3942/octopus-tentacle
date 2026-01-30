<?php

namespace App\Http\Controllers;

use App\Services\VideoServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller
{
    private const VIEW_TTL = 300; // 5 minutes

    public function __construct(
        protected VideoServices $videoServices
    ) {}

    public function index(Request $request, int $page = 1)
    {
        $tentacleId = $request->attributes->get('tentacle_id');
        $isAjax = $request->ajax();

        // View cache (only page 1, non-AJAX)
        $viewCacheKey = 'cache:view:page:pageHome';
        if (! $isAjax && $page == 1) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        $result = $this->videoServices->getFeedVideos($tentacleId, $page, $request);

        // AJAX request returns array
        if (is_array($result)) {
            return response()->json($result);
        }

        $view = view('page.pageHome', [
            'videos' => $result,
            'h1' => 'Latest Videos',
            'h2' => 'Discover our collection',
        ])->render();

        if (! $isAjax && $page == 1) {
            Redis::setex($viewCacheKey, self::VIEW_TTL, $view);
        }

        return response($view);
    }
}
