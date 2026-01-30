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

    @if(empty($query) || ($totalVideos === 0 && $totalChannels === 0 && $totalTags === 0))
        {{-- No results --}}
        <div class="search-no-results">
            @if(empty($query))
                <h1>{{ t__('pages.search.placeholder') }}</h1>
            @else
                <h1>{{ t__('pages.search.no_results', ['query' => $query]) }}</h1>
            @endif
            <p>{{ t__('pages.search.try_different') }}</p>
            <div class="search-suggestions">
                <a href="{{ route('home') }}" class="suggestion-link">{{ t__('pages.search.see_all_videos') }}</a>
                <a href="{{ route('category.index') }}" class="suggestion-link">{{ t__('pages.search.explore_categories') }}</a>
                <a href="{{ route('model.index') }}" class="suggestion-link">{{ t__('pages.search.see_all_models') }}</a>
            </div>
        </div>
    @else
        {{-- Results --}}
        <div class="search-results-summary">
            <h1>{{ t__('pages.search.results_for', ['query' => $query]) }}</h1>
            <p class="results-count">
                @if($totalVideos > 0)
                    {{ $totalVideos }} {{ $totalVideos === 1 ? t__('common.video') : t__('common.videos') }}
                @endif
                @if($totalChannels > 0)
                    {{ $totalVideos > 0 ? ', ' : '' }}{{ $totalChannels }} {{ $totalChannels === 1 ? t__('common.model') : t__('common.models') }}
                @endif
                @if($totalTags > 0)
                    {{ $totalVideos > 0 || $totalChannels > 0 ? ', ' : '' }}{{ $totalTags }} {{ $totalTags === 1 ? t__('common.category') : t__('common.categories') }}
                @endif
            </p>
        </div>

        {{-- Channels Section (First) --}}
        @if($totalChannels > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.models.title') }} ({{ $totalChannels }})</h2>
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
        @if($totalTags > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">{{ t__('pages.categories.title') }} ({{ $totalTags }})</h2>
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
                    @foreach ($allVideos as $video)
                        @include('components.cardVideo', ['video' => $video])
                    @endforeach
                </div>

                @if($allVideos instanceof \Illuminate\Pagination\LengthAwarePaginator && $allVideos->hasPages())
                    @include('components.pagination-simple', ['paginator' => $allVideos])
                @endif
            </div>
        @endif
    @endif

</main>

@endsection
