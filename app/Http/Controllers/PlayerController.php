<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Traits\PremiumHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class PlayerController extends Controller
{
    use PremiumHandler;

    public function index(Request $request, int $id): \Illuminate\View\View
    {
        $tentacleId = $request->attributes->get('tentacle_id');

        $cacheKeyPlayer = "cache:data:player:video:id:{$id}";

        try {
            $cachedPlayer = Redis::get($cacheKeyPlayer);
        } catch (\Exception $e) {
            $cachedPlayer = null;
        }

        if ($cachedPlayer) {
            $data = json_decode($cachedPlayer, true);
        } else {
            $video = Video::select('id', 'player_voe_url', 'player_bunny_url', 'player_bunny_url_2', 'player_babastream_url')
                ->where('id', $id)
                ->with(['translations' => fn ($q) => $q->select('video_id', 'title')])
                ->firstOrFail();

            $data = [
                'id' => $video->id,
                'player_voe_url' => $video->player_voe_url,
                'player_bunny_url' => $video->player_bunny_url,
                'player_bunny_url_2' => $video->player_bunny_url_2,
                'player_babastream_url' => $video->player_babastream_url,
                'translations' => $video->translations->map(fn ($t) => ['video_id' => $t->video_id, 'title' => $t->title])->toArray(),
            ];

            try {
                Redis::setex($cacheKeyPlayer, 600, json_encode($data));
            } catch (\Exception $e) {
                // Redis unavailable, continue without cache
            }
        }

        $title = $data['translations'][0]['title'] ?? 'Video '.$id;
        $playerBunnyUrl = $data['player_bunny_url'] ?? $data['player_bunny_url_2'] ?? null;
        $playerBabastreamUrl = ! $playerBunnyUrl ? ($data['player_babastream_url'] ?? null) : null;
        $playerVoeUrl = $data['player_voe_url'] ?? null;

        $popupConfig = popup_config();

        return view('page.iframe.adsVideoPlayer', [
            'isPremium' => $this->isPremiumUser(),
            'videoId' => (int) $id,
            'title' => $title,
            'playerBunnyUrl' => $playerBunnyUrl,
            'playerBabastreamUrl' => $playerBabastreamUrl,
            'playerVoeUrl' => $playerVoeUrl,
            'popupConfig' => $popupConfig,
        ]);
    }
}
