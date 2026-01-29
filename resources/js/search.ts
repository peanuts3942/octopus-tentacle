interface Video {
    id: number;
    title: string;
    slug: string;
    thumbnail: string;
    channel: string;
    url: string;
}

interface Channel {
    id: number;
    name: string;
    slug: string;
    avatar: string | null;
    url: string;
}

interface Tag {
    id: number;
    name: string;
    slug: string;
    url: string;
}

interface AutocompleteResults {
    videos: Video[];
    videoCount: number;
    channels: Channel[];
    tags: Tag[];
}

export function initSearch() {
    // Initialiser la recherche pour toutes les barres de recherche (desktop et mobile)
    const searchContainers = document.querySelectorAll('.search-bar-container');
    
    searchContainers.forEach(container => {
        initSearchInstance(container as HTMLElement);
    });
}

function initSearchInstance(searchBarContainer: HTMLElement) {
    const searchInput = searchBarContainer.querySelector('.search-input') as HTMLInputElement;
    const autocompleteContainer = searchBarContainer.querySelector('.search-autocomplete') as HTMLElement;
    const autocompleteContent = searchBarContainer.querySelector('.autocomplete-content') as HTMLElement;

    if (!searchInput) {
        return;
    }

    // Check if autocomplete is enabled
    const autocompleteEnabled = searchBarContainer?.dataset.autocompleteEnabled === 'true';

    if (!autocompleteEnabled || !autocompleteContainer || !autocompleteContent) {
        // Autocomplete disabled, just allow form submission
        return;
    }

    let debounceTimer: any;
    let currentQuery = '';
    let abortController: AbortController | null = null;

    // Debounced search function
    function performSearch(query: string) {
        // Clear previous timer
        clearTimeout(debounceTimer);

        // Hide if query too short
        if (query.length < 2) {
            hideAutocomplete();
            return;
        }

        // Debounce 300ms
        debounceTimer = setTimeout(async () => {
            currentQuery = query;

            // Abort previous request if any
            if (abortController) {
                abortController.abort();
            }

            abortController = new AbortController();

            try {
                const response = await fetch(`/search/autocomplete?q=${encodeURIComponent(query)}`, {
                    signal: abortController.signal
                });

                if (!response.ok) {
                    throw new Error('Search failed');
                }

                const data: AutocompleteResults = await response.json();

                // Only show results if query hasn't changed
                if (query === currentQuery) {
                    renderAutocomplete(data, query);
                }
            } catch (error: any) {
                if (error.name !== 'AbortError') {
                    console.error('Search error:', error);
                    hideAutocomplete();
                }
            }
        }, 300);
    }

    // Render autocomplete results
    function renderAutocomplete(results: AutocompleteResults, query: string) {
        const hasResults = results.videoCount > 0 || results.channels.length > 0 || results.tags.length > 0;

        if (!hasResults) {
            autocompleteContent!.innerHTML = `
                <div class="autocomplete-empty">
                    <p>No results found for "${escapeHtml(query)}"</p>
                </div>
            `;
            showAutocomplete();
            return;
        }

        let html = '';

        // Channels section (first)
        if (results.channels.length > 0) {
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-section-title">Channels</div>';
            results.channels.forEach(channel => {
                html += `
                    <a href="${channel.url}" class="autocomplete-item autocomplete-channel">
                        <span class="autocomplete-channel-name">${escapeHtml(channel.name)}</span>
                    </a>
                `;
            });
            html += '</div>';
        }

        // Tags section (second)
        if (results.tags.length > 0) {
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-section-title">Tags</div>';
            results.tags.forEach(tag => {
                html += `
                    <a href="${tag.url}" class="autocomplete-item autocomplete-tag">
                        <span class="autocomplete-tag-name">${escapeHtml(tag.name)}</span>
                    </a>
                `;
            });
            html += '</div>';
        }

        // Videos section - show suggestions or count
        if (results.videos.length > 0) {
            // Show video thumbnails
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-section-title">Videos</div>';
            results.videos.forEach(video => {
                html += `
                    <a href="${video.url}" class="autocomplete-item autocomplete-video">
                        <img src="${video.thumbnail}" alt="${escapeHtml(video.title)}" class="autocomplete-video-thumb">
                        <div class="autocomplete-video-info">
                            <div class="autocomplete-video-title">${escapeHtml(video.title)}</div>
                            <div class="autocomplete-video-channel">${escapeHtml(video.channel)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        } else if (results.videoCount > 0) {
            // Show video count only
            html += '<div class="autocomplete-section">';
            html += '<div class="autocomplete-section-title">Videos</div>';
            html += `
                <div class="autocomplete-video-count">
                    ${results.videoCount} video${results.videoCount !== 1 ? 's' : ''} found
                </div>
            `;
            html += '</div>';
        }

        // See all results link
        html += `
            <a href="/search?q=${encodeURIComponent(query)}" class="autocomplete-see-all">
                See all results for "${escapeHtml(query)}"
            </a>
        `;

        autocompleteContent!.innerHTML = html;
        showAutocomplete();
    }

    function showAutocomplete() {
        autocompleteContainer!.classList.remove('hidden');
    }

    function hideAutocomplete() {
        autocompleteContainer!.classList.add('hidden');
        autocompleteContent!.innerHTML = '';
    }

    function escapeHtml(text: string): string {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Event listeners
    searchInput.addEventListener('input', (e) => {
        const query = (e.target as HTMLInputElement).value.trim();
        performSearch(query);
    });

    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length >= 2 && autocompleteContent!.innerHTML) {
            showAutocomplete();
        }
    });

    // Close autocomplete when clicking outside
    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;

        if (searchBarContainer && !searchBarContainer.contains(target)) {
            hideAutocomplete();
        }
    });

    // Close on Escape key
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideAutocomplete();
            searchInput.blur();
        }
    });
}
