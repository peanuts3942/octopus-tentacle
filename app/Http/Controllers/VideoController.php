<?php

namespace App\Http\Controllers;

use App\Services\VideoServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class VideoController extends Controller
{
    private const VIEW_TTL = 300; // 5 minutes

    public function __construct(
        protected VideoServices $videoServices
    ) {}

    public function show(Request $request, int $id, string $slug)
    {
        $tentacleId = $request->attributes->get('tentacle_id');
        $isAjax = $request->ajax();

        // View cache (non-AJAX only)
        $viewCacheKey = "cache:view:page:pageVideo:{$id}";
        if (! $isAjax) {
            $cachedView = Redis::get($viewCacheKey);
            if ($cachedView) {
                return response($cachedView);
            }
        }

        $video = $this->videoServices->getVideo($id);

        if (! $video) {
            abort(404);
        }

        // Redirect to correct slug if necessary
        if ($video->slug !== $slug) {
            return redirect()->route('video.show', [
                'id' => $video->id,
                'slug' => $video->slug,
            ], 301);
        }

        // Get related videos
        $relatedVideos = $this->videoServices->getRelatedVideos($tentacleId, $video);

        $view = view('page.pageVideo', [
            'video' => $video,
            'relatedVideos' => $relatedVideos,
        ])->render();

        if (! $isAjax) {
            Redis::setex($viewCacheKey, self::VIEW_TTL, $view);
        }

        return response($view);
    }

    public function relatedVideos(Request $request, int $id): JsonResponse
    {
        $page = max((int) $request->input('page', 2), 1);
        $tentacleId = config('app.tentacle_id');

        $video = $this->videoServices->getVideo($id);

        if (! $video) {
            abort(404);
        }

        $relatedVideos = $this->videoServices->getRelatedVideos($tentacleId, $video, $page);

        $html = '';
        foreach ($relatedVideos as $relatedVideo) {
            $html .= view('components.cardVideo', ['video' => $relatedVideo])->render();
        }

        return response()->json([
            'html' => $html,
            'hasMore' => $relatedVideos->hasMorePages(),
            'nextPage' => $page + 1,
        ]);
    }
}
