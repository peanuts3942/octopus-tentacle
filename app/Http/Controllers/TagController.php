<?php

namespace App\Http\Controllers;

class TagController extends Controller
{
    public function index()
    {
        // Mock categories data
        $categories = collect(range(1, 20))->map(fn ($i) => (object) [
            'id' => $i,
            'name' => "Category {$i}",
            'slug' => "category-{$i}",
        ]);

        return view('page.pageCategories', [
            'categories' => $categories,
            'h1' => 'All Categories',
            'h2' => 'Browse videos by category',
        ]);
    }

    public function show(string $slug)
    {
        // Mock category data
        $category = (object) [
            'id' => 1,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
        ];

        // Mock videos
        $videos = collect(range(1, 24))->map(fn ($i) => (object) [
            'id' => $i,
            'title' => "Video in {$category->name} #{$i}",
            'slug' => "video-{$slug}-{$i}",
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

        return view('page.pageCategory', [
            'category' => $category,
            'videos' => $videos,
        ]);
    }
}
