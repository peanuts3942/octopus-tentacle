<header class="wrapper header-desktop">
    <a href="{{ route('home') }}" class="logo-header">
        @if(theme('logo_url'))
            <img src="{{ theme('logo_url') }}" alt="{{ theme('site_name', config('app.name')) }}" class="h-8">
        @else
            <span class="text-2xl font-bold text-white">{{ theme('site_name', config('app.name')) }}</span>
        @endif
    </a>

    <nav itemscope itemtype="https://schema.org/SiteNavigationElement" class="ml-auto">
        <ul class="flex items-center justify-center gap-8">
            <li>
                <a href="{{ route('home') }}" class="menu-link trans" itemprop="url">
                    <span>Vidéos</span>
                </a>
            </li>
            <li>
                <a href="{{ route('category.index') }}" class="menu-link trans" itemprop="url">
                    <span>Catégories</span>
                </a>
            </li>
            <li>
                <a href="{{ route('model.index') }}" class="menu-link trans" itemprop="url">
                    <span>Modèles</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="search-bar-header search-bar-container" data-autocomplete-enabled="{{ config('search.enable_autocomplete', true) ? 'true' : 'false' }}">
        <form action="{{ route('search') }}" method="GET" class="search-form-header">
            <input
                class="search-bar search-input"
                type="text"
                name="q"
                placeholder="Rechercher..."
                autocomplete="off"
            >
            <button type="submit" class="search-submit-btn" aria-label="Search">
                @include('icons.search')
            </button>
        </form>

        {{-- Autocomplete dropdown (only if enabled) --}}
        @if (config('search.enable_autocomplete', true))
            <div class="search-autocomplete hidden">
                <div class="autocomplete-content"></div>
            </div>
        @endif
    </div>

</header>
