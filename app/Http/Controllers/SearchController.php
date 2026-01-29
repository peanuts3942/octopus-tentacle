<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');

        // Mock data
        $mockVideos = collect(range(1, 12))->map(fn ($i) => (object) [
            'id' => $i,
            'title' => "Search Result Video {$i}",
            'slug' => "search-result-{$i}",
            'thumbnail_url' => 'https://placehold.co/320x180/1a1a2e/FFFFFF?text=Result+'.$i,
            'preview_url' => null,
            'time' => rand(60, 600),
            'published_at' => now()->subDays(rand(1, 30)),
            'channel' => (object) [
                'id' => 1,
                'name' => 'Channel Name',
                'slug' => 'channel-name',
            ],
        ]);

        $videos = new LengthAwarePaginator($mockVideos, 50, 24, 1, ['path' => route('search')]);

        $channels = collect(range(1, 4))->map(fn ($i) => (object) [
            'id' => $i,
            'name' => "Model Result {$i}",
            'slug' => "model-result-{$i}",
        ]);

        $tags = collect(range(1, 4))->map(fn ($i) => (object) [
            'id' => $i,
            'name' => "Category Result {$i}",
            'slug' => "category-result-{$i}",
        ]);

        $allVideos = new LengthAwarePaginator($mockVideos, 100, 24, 1, ['path' => route('search')]);

        return view('page.pageSearch', [
            'query' => $query,
            'videos' => $videos,
            'channels' => $channels,
            'tags' => $tags,
            'allVideos' => $allVideos,
            'totalVideos' => empty($query) ? 0 : 50,
            'totalChannels' => empty($query) ? 0 : 4,
            'totalTags' => empty($query) ? 0 : 4,
        ]);
    }
}
