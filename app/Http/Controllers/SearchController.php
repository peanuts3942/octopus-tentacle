<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Tag;
use App\Models\Video;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (empty($query)) {
            return view('page.pageSearch', [
                'query' => '',
                'videos' => collect(),
                'channels' => collect(),
                'tags' => collect(),
                'allVideos' => collect(),
                'totalVideos' => 0,
                'totalChannels' => 0,
                'totalTags' => 0,
            ]);
        }

        // Search videos via translations
        $videos = Video::query()
            ->select('videos.*')
            ->whereHas('translation', fn ($q) => $q->where('title', 'LIKE', "%{$query}%"))
            ->where('videos.is_published', true)
            ->notDraftForTentacle()
            ->availableInZone()
            ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
            ->orderBy('videos.published_at', 'desc')
            ->limit(12)
            ->get();

        // Search channels
        $channels = Channel::where('name', 'LIKE', "%{$query}%")
            ->limit(4)
            ->get();

        // Search tags via translations
        $tags = Tag::whereHas('translation', fn ($q) => $q->where('name', 'LIKE', "%{$query}%"))
            ->with('translation')
            ->limit(4)
            ->get();

        // All videos paginated for full results
        $allVideos = Video::query()
            ->select('videos.*')
            ->whereHas('translation', fn ($q) => $q->where('title', 'LIKE', "%{$query}%"))
            ->where('videos.is_published', true)
            ->notDraftForTentacle()
            ->availableInZone()
            ->with(['channel:id,name,slug,profile_picture_url', 'translation'])
            ->orderBy('videos.published_at', 'desc')
            ->paginate(24)
            ->appends(['q' => $query]);

        return view('page.pageSearch', [
            'query' => $query,
            'videos' => $videos,
            'channels' => $channels,
            'tags' => $tags,
            'allVideos' => $allVideos,
            'totalVideos' => $allVideos->total(),
            'totalChannels' => $channels->count(),
            'totalTags' => $tags->count(),
        ]);
    }
}
