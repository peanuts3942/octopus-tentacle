<?php

namespace App\Http\Controllers;

class VideoController extends Controller
{
    public function show(int $id, string $slug)
    {
        // Mock video data
        $video = (object) [
            'id' => $id,
            'title' => 'Video Title '.$id,
            'slug' => $slug,
            'thumbnail_url' => 'https://placehold.co/640x360/1a1a2e/FFFFFF?text=Video+'.$id,
            'preview_url' => null,
            'player_url' => null,
            'time' => 360,
            'views' => rand(1000, 50000),
            'published_at' => now()->subDays(rand(1, 30)),
            'channel' => (object) [
                'id' => 1,
                'name' => 'Channel Name',
                'slug' => 'channel-name',
            ],
            'tags' => collect([
                (object) ['id' => 1, 'name' => 'Category 1', 'slug' => 'category-1'],
                (object) ['id' => 2, 'name' => 'Category 2', 'slug' => 'category-2'],
            ]),
        ];

        // Mock related videos
        $relatedVideos = collect(range(1, 12))->map(fn ($i) => (object) [
            'id' => $id + $i,
            'title' => "Related Video {$i}",
            'slug' => "related-video-{$i}",
            'thumbnail_url' => 'https://placehold.co/320x180/1a1a2e/FFFFFF?text=Related+'.$i,
            'preview_url' => null,
            'time' => rand(60, 600),
            'published_at' => now()->subDays(rand(1, 30)),
            'channel' => (object) [
                'id' => 1,
                'name' => 'Channel Name',
                'slug' => 'channel-name',
            ],
        ]);

        return view('page.pageVideo', [
            'video' => $video,
            'relatedVideos' => $relatedVideos,
        ]);
    }
}
