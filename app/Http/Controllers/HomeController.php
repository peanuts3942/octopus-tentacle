<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HomeController extends Controller
{
    public function index(Request $request, int $page = 1)
    {
        // Mock data for skeleton
        $mockVideos = collect(range(1, 24))->map(fn ($i) => (object) [
            'id' => $i,
            'title' => "Video Title {$i}",
            'slug' => "video-title-{$i}",
            'thumbnail_url' => 'https://placehold.co/320x180/1a1a2e/FFFFFF?text=Video+'.$i,
            'preview_url' => null,
            'time' => rand(60, 600),
            'published_at' => now()->subDays(rand(1, 30)),
            'channel' => (object) [
                'id' => 1,
                'name' => 'Channel Name',
                'slug' => 'channel-name',
            ],
        ]);

        $videos = new LengthAwarePaginator(
            $mockVideos,
            120,
            24,
            $page,
            ['path' => route('home')]
        );

        return view('page.pageHome', [
            'videos' => $videos,
            'h1' => 'Latest Videos',
            'h2' => 'Discover our collection',
        ]);
    }
}
