<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="screen-width" id="screen-width" content="">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="rating" content="adult">

        @if(config('tentacle.settings.seo_enable_referencing', false))
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        @else
            <meta name="robots" content="noindex, nofollow">
        @endif

        @php
            $routeName = Route::currentRouteName();
            $defaultImage = 'images/share.jpg';

            // Title
            $title = match($routeName) {
                'home' => seo_meta($textseo, 'home', 'meta_title'),
                'videos.index' => seo_meta($textseo, 'videos', 'meta_title'),
                'video.show' => seo_meta($textseo, 'video', 'meta_title', [
                    'name' => $video->title ?? '',
                ]),
                'category.index' => seo_meta($textseo, 'categories', 'meta_title'),
                'category.show' => seo_meta($textseo, 'category', 'meta_title', [
                    'name' => $category->name ?? '',
                ]),
                'model.index' => seo_meta($textseo, 'models', 'meta_title'),
                'model.show' => seo_meta($textseo, 'model', 'meta_title', [
                    'name' => $channel->name ?? '',
                ]),
                'tag.index' => seo_meta($textseo, 'tags', 'meta_title'),
                'tag.show' => seo_meta($textseo, 'tag', 'meta_title', [
                    'name' => $tag->name ?? '',
                ]),
                'search.page' => seo_meta($textseo, 'search', 'meta_title'),
                'search.results' => seo_meta($textseo, 'searchresults', 'meta_title', [
                    'query' => $search ?? '',
                ]),
                'legal.dmca' => 'DMCA',
                'legal.remove' => 'Remove Content',
                default => config('app.name'),
            };

            // Fallback if empty
            $title = $title ?: theme('site_name', config('app.name'));

            // Description
            $description = match($routeName) {
                'home' => seo_meta($textseo, 'home', 'meta_description'),
                'videos.index' => seo_meta($textseo, 'videos', 'meta_description'),
                'video.show' => seo_meta($textseo, 'video', 'meta_description', [
                    'description' => $video->translation?->short_description ?? '',
                ]),
                'category.index' => seo_meta($textseo, 'categories', 'meta_description'),
                'category.show' => seo_meta($textseo, 'category', 'meta_description', [
                    'name' => $category->name ?? '',
                ]),
                'model.index' => seo_meta($textseo, 'models', 'meta_description'),
                'model.show' => seo_meta($textseo, 'model', 'meta_description', [
                    'name' => $channel->name ?? '',
                ]),
                'tag.index' => seo_meta($textseo, 'tags', 'meta_description'),
                'tag.show' => seo_meta($textseo, 'tag', 'meta_description', [
                    'name' => $tag->name ?? '',
                ]),
                'search.page' => seo_meta($textseo, 'search', 'meta_description'),
                'search.results' => seo_meta($textseo, 'searchresults', 'meta_description', [
                    'query' => $search ?? '',
                ]),
                default => '',
            };

            // Image
            $image = match($routeName) {
                'video.show' => isset($video->thumbnail_url) && $video->thumbnail_url
                    ? (str_starts_with($video->thumbnail_url, 'https://') ? $video->thumbnail_url : asset('/storage/' . $video->thumbnail_url))
                    : asset($defaultImage),
                default => asset($defaultImage),
            };

            // URL
            $url = url()->current();
        @endphp

        <style>
            html, body {
                background-color: #000000 !important;
            }
            :root {
                --main-color: {{ theme('primary_color', '#E85D04') }};
                --font-sans: '{{ theme('font_family') }}', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
            }
            @layer utilities {
                body,
                html {
                    --font-sans: '{{ theme('font_family') }}', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
                }
            }
        </style>

        @if(theme('favicon_url'))
            <link rel="icon" href="{{ theme('favicon_url') }}" type="image/x-icon">
        @endif

        @if(theme('font_url'))
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="{{ theme('font_url') }}" rel="stylesheet" />
        @endif

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ $image }}">
        <meta property="og:url" content="{{ $url }}">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $image }}">

        <link rel="canonical" href="{{ $url }}"/>

        @vite(['resources/css/app.css', 'resources/js/app.ts'])

        @include('partials.js-translations')

    </head>
    <body>
        @include('components.header')
        <x-sticky-header />

        @yield('content')

        @include('components.footer')

        @include('components.tabbar')

    </body>
</html>
