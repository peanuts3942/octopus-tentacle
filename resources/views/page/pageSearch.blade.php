@extends('app')

@section('content')

<main class="wrapper" id="page-search">

    <x-breadcrumb :items="[
        ['name' => t__('navigation.home'), 'url' => route('home')],
        ['name' => t__('pages.search.title'), 'url' => '#']
    ]" />

    {{-- Search Bar --}}
    <div class="search-container">
        <form action="{{ route('search') }}" method="GET" class="search-form">
            <input
                type="text"
                name="q"
                class="search-input"
                placeholder="{{ t__('pages.search.placeholder') }}"
                value="{{ $query }}"
                autofocus
            >
            <button type="submit" class="search-submit-btn">
                @include('icons.search')
            </button>
        </form>
    </div>

    @if(empty($query))
        {{-- Empty search --}}
        <div class="search-no-results">
            <h1>{{ t__('pages.search.placeholder') }}</h1>
        </div>
    @elseif($hasNoResults)
        {{-- No video results --}}
        <div class="search-no-results">
            <h1>{{ t__('pages.search.no_results', ['query' => $query]) }}</h1>
            <p>{{ t__('pages.search.try_different') }}</p>
        </div>

        {{-- Channels found (even if no videos) --}}
        @if(count($channels) > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.models.title') }} ({{ count($channels) }})</h2>
                </div>

                <div class="tags-grid">
                    @foreach ($channels as $channel)
                        <a href="{{ route('model.show', ['slug' => $channel->slug]) }}" class="tag-name trans">
                            <h3 class="tag-name-text trans">{{ $channel->name }}</h3>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tags found (even if no videos) --}}
        @if(count($tags) > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.categories.title') }} ({{ count($tags) }})</h2>
                </div>

                <div class="tags-grid">
                    @foreach ($tags as $tag)
                        <a href="{{ route('category.show', ['slug' => $tag->slug]) }}" class="tag-name trans">
                            <h3 class="tag-name-text trans">{{ $tag->name }}</h3>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- More videos section (feed) --}}
        @if($feedVideos && count($feedVideos) > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.search.more_videos') }}</h2>
                </div>

                <div class="videos-grid">
                    @foreach ($feedVideos as $video)
                        @include('components.cardVideo', ['video' => $video])
                    @endforeach
                </div>

                @if($feedVideos->hasPages())
                    @include('components.pagination-simple', ['paginator' => $feedVideos, 'basePath' => '/page'])
                @endif
            </div>
        @endif
    @else
        {{-- Results found --}}
        <div class="search-results-summary">
            <h1>{{ t__('pages.search.results_for', ['query' => $query]) }}</h1>
            <p class="results-count">
                @if($totalVideos > 0)
                    {{ $totalVideos }} {{ $totalVideos === 1 ? t__('common.video') : t__('common.videos') }}
                @endif
                @if(count($channels) > 0)
                    {{ $totalVideos > 0 ? ', ' : '' }}{{ count($channels) }} {{ count($channels) === 1 ? t__('common.model') : t__('common.models') }}
                @endif
                @if(count($tags) > 0)
                    {{ $totalVideos > 0 || count($channels) > 0 ? ', ' : '' }}{{ count($tags) }} {{ count($tags) === 1 ? t__('common.category') : t__('common.categories') }}
                @endif
            </p>
        </div>

        {{-- Channels Section (First) --}}
        @if(count($channels) > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.models.title') }} ({{ count($channels) }})</h2>
                </div>

                <div class="tags-grid">
                    @foreach ($channels as $channel)
                        <a href="{{ route('model.show', ['slug' => $channel->slug]) }}" class="tag-name trans">
                            <h3 class="tag-name-text trans">{{ $channel->name }}</h3>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tags Section (Second) --}}
        @if(count($tags) > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.categories.title') }} ({{ count($tags) }})</h2>
                </div>

                <div class="tags-grid">
                    @foreach ($tags as $tag)
                        <a href="{{ route('category.show', ['slug' => $tag->slug]) }}" class="tag-name trans">
                            <h3 class="tag-name-text trans">{{ $tag->name }}</h3>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Videos Section (Third) --}}
        @if($totalVideos > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.videos.title') }} ({{ $totalVideos }})</h2>
                </div>

                <div class="videos-grid">
                    @foreach ($videos as $video)
                        @include('components.cardVideo', ['video' => $video])
                    @endforeach
                </div>

                @if($videos->hasPages())
                    @include('components.pagination-simple', ['paginator' => $videos])
                @endif
            </div>
        @endif
    @endif

</main>

@endsection
