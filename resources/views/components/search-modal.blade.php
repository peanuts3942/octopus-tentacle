<search-modal class="{{ $class ?? '' }}">

    <!-- <button class="search-close" open-search-modal>
        @include('icons.search')
    </button> -->

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
    </div>

    

</search-modal>
