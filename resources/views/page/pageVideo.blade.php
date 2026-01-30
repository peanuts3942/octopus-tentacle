@extends('app')

@section('content')

<main id="page-video">

    @php
        $breadcrumbItems = [
            ['name' => t__('navigation.home'), 'url' => route('home')],
        ];

        if ($video->channel && $video->channel->slug) {
            $breadcrumbItems[] = [
                'name' => $video->channel->name,
                'url' => route('model.show', ['slug' => $video->channel->slug])
            ];
        }

        $breadcrumbItems[] = ['name' => $video->title, 'url' => '#'];
    @endphp

    <x-breadcrumb :items="$breadcrumbItems" />

    <div class="player-wrapper">
        <img class="background-video-player" src="{{ $video->thumbnail_url }}"/>
        <div class="player-container">
            @if($video->player_url)
                <iframe
                    src="{{ $video->player_url }}"
                    class="video-player-iframe"
                    allowfullscreen
                    frameborder="0"
                    scrolling="no"
                    allow="autoplay; encrypted-media"
                ></iframe>
            @else
                <div class="player-broken-container">
                    <p class="player-broken-text">{{ t__('common.player_unavailable') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="wrapper">
        <div class="video-infos">
            <h1 class="video-title">{{ $video->title }}</h1>
            <div class="video-date-channel-container">
                <p class="video-date">{{ $video->published_at?->translatedFormat('j F Y') ?? t__('common.unknown_date') }}</p>
                @if($video->channel)
                <span class="video-separator">•</span>
                <h4 class="video-channel trans">
                    <a href="{{ route('model.show', ['slug' => $video->channel->slug]) }}">{{ $video->channel->name }}</a>
                </h4>
                @endif
            </div>
            @if(!empty($video->tags))
            <ul class="video-categories">
                @foreach($video->tags as $tag)
                    <li>
                        <a href="{{ route('category.show', ['slug' => $tag->slug]) }}" class="video-category video_metadata_box_tag">{{ $tag->name }}</a>
                    </li>
                @endforeach
            </ul>
            @endif
        </div>

        <div class="heading-container">
            <span class="heading-text">{{ t__('tab_filters.more_videos') }}</span>
        </div>

        <div class="videos-grid">
            @foreach ($relatedVideos as $relatedVideo)
                @include('components.cardVideo', ['video' => $relatedVideo])
            @endforeach
        </div>

        @if(method_exists($relatedVideos, 'previousPageUrl'))
            @include('components.pagination-simple', ['paginator' => $relatedVideos])
        @endif

    </div>

</main>

@endsection
