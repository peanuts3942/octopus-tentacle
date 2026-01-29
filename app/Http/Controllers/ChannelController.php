<?php

namespace App\Http\Controllers;

class ChannelController extends Controller
{
    public function index()
    {
        // Mock channels data
        $channels = collect(range(1, 20))->map(fn ($i) => (object) [
            'id' => $i,
            'name' => "Model {$i}",
            'slug' => "model-{$i}",
        ]);

        return view('page.pageModels', [
            'channels' => $channels,
            'h1' => 'All Models',
            'h2' => 'Browse videos by model',
        ]);
    }

    public function show(string $slug)
    {
        // Mock channel data
        $channel = (object) [
            'id' => 1,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => 'Model description placeholder',
        ];

        // Mock videos
        $videos = collect(range(1, 24))->map(fn ($i) => (object) [
            'id' => $i,
            'title' => "Video by {$channel->name} #{$i}",
            'slug' => "video-{$slug}-{$i}",
            'thumbnail_url' => 'https://placehold.co/320x180/1a1a2e/FFFFFF?text=Video+'.$i,
            'preview_url' => null,
            'time' => rand(60, 600),
            'published_at' => now()->subDays(rand(1, 30)),
            'channel' => $channel,
        ]);

        return view('page.pageModel', [
            'channel' => $channel,
            'videos' => $videos,
        ]);
    }
}
