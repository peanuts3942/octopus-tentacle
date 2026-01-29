@if ($paginator->hasPages())
<nav class="pagination" role="navigation" aria-label="Pagination">

    <div class="pagination-buttons">
        {{-- Bouton Précédent --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-button pagination-button--disabled" aria-disabled="true">
                <svg class="pagination-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination-button" rel="prev">
                <svg class="pagination-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>
        @endif

        {{-- Numéros de pages --}}
        <div class="pagination-numbers">
            @php
                $start = max($paginator->currentPage() - 1, 1);
                $end = min($paginator->currentPage() + 1, $paginator->lastPage());
                if ($paginator->currentPage() == 1) {
                    $end = $paginator->currentPage() + 3;
                }
            @endphp

            {{-- Première page --}}
            @if ($start > 1)
                <a href="{{ $paginator->url(1) }}" class="pagination-number-button">1</a>
                @if ($start > 2)
                    <span class="pagination-dots">...</span>
                @endif
            @endif

            {{-- Pages du milieu --}}
            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $paginator->currentPage())
                    <span class="pagination-number-button pagination-number-button--active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="pagination-number-button">{{ $page }}</a>
                @endif
            @endfor

            {{-- Dernière page --}}
            @if ($end < $paginator->lastPage())
                @if ($end < $paginator->lastPage() - 1)
                    <span class="pagination-dots">...</span>
                @endif
                <a href="{{ $paginator->url($paginator->lastPage()) }}" class="pagination-number-button">{{ $paginator->lastPage() }}</a>
            @endif
        </div>

        {{-- Bouton Suivant --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination-button" rel="next">
                <svg class="pagination-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </a>
        @else
            <span class="pagination-button pagination-button--disabled" aria-disabled="true">
                <svg class="pagination-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </span>
        @endif
    </div>
</nav>
@endif

