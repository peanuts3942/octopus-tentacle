@extends('app')

@section('content')

<main class="wrapper" id="page-search">

    <x-breadcrumb :items="[
        ['name' => 'Accueil', 'url' => route('home')],
        ['name' => 'Recherche ' . $query, 'url' => '#']
    ]" />

    {{-- Search Bar --}}
    <div class="search-container">
        <form action="{{ route('search') }}" method="GET" class="search-form">
            <input
                type="text"
                name="q"
                class="search-input"
                placeholder="Rechercher des vidéos, modèles ou catégories..."
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
                <h1>Rechercher des vidéos, modèles ou catégories...</h2>
            @else
                <h1>Aucun résultat trouvé pour "{{ $query }}"</h2>
            @endif
            <p>Essayez de modifier vos termes de recherche ou explorez les catégories et les modèles</p>
            <div class="search-suggestions">
                <a href="{{ route('home') }}" class="suggestion-link">Voir toutes les videos</a>
                <a href="{{ route('category.index') }}" class="suggestion-link">Explorer les categories</a>
                <a href="{{ route('model.index') }}" class="suggestion-link">Voir toutes les modèles</a>
            </div>
        </div>
    @else
        {{-- Results --}}
        <div class="search-results-summary">
            <h1>Résultats de la recherche pour "{{ $query }}"</h1>
            <p class="results-count">
                @if($totalVideos > 0)
                    {{ $totalVideos }} {{ $totalVideos === 1 ? 'vidéo' : 'vidéos' }}
                @endif
                @if($totalChannels > 0)
                    {{ $totalVideos > 0 ? ', ' : '' }}{{ $totalChannels }} {{ $totalChannels === 1 ? 'modèle' : 'modèles' }}
                @endif
                @if($totalTags > 0)
                    {{ $totalVideos > 0 || $totalChannels > 0 ? ', ' : '' }}{{ $totalTags }} {{ $totalTags === 1 ? 'catégorie' : 'catégories' }}
                @endif
            </p>
        </div>

        {{-- Channels Section (First) --}}
        @if($totalChannels > 0)
            <div class="search-section">
                <div class="heading-container">
                    <h2 class="heading-text">Modèles ({{ $totalChannels }})</h2>
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
                    <h2 class="heading-text">Catégories ({{ $totalTags }})</h2>
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
                    <h2 class="heading-text">Vidéos ({{ $totalVideos }})</h2>
                </div>

                <div class="videos-grid">
                    @foreach ($videos as $video)
                        @include('components.cardVideo', ['video' => $video])
                    @endforeach
                </div>

                @if($videos->hasPages() && $totalVideos > 24)
                    @include('components.pagination-simple', ['paginator' => $videos])
                @endif
            </div>
        @endif
    @endif

    @if(empty($query) || $totalVideos < 24)

        <div class="heading-container heading-container-search-all-videos">
            <h2 class="heading-text">Toutes les videos</h2>
        </div>

        <div class="videos-grid">
            @foreach ($allVideos as $video)
                @include('components.cardVideo', ['video' => $video])
            @endforeach
        </div>

        @include('components.pagination-simple', ['paginator' => $allVideos])

    @endif

</main>

@endsection
