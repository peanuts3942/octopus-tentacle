function initShowMoreVideos() {
    const button = document.getElementById('show-more-videos');
    if (!button) return;

    const label = window.t ? window.t('common.show_more_videos') : button.textContent.trim();

    button.addEventListener('click', async () => {
        const baseUrl = button.dataset.relatedUrl;
        const nextPage = button.dataset.nextPage;

        button.disabled = true;
        button.classList.add('loading');

        try {
            const response = await fetch(`${baseUrl}?page=${nextPage}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Network error');

            const data = await response.json();

            const grid = document.getElementById('related-videos-grid');
            if (grid && data.html) {
                grid.insertAdjacentHTML('beforeend', data.html);

                if (window.formatElements) window.formatElements(grid);
                if (window.initVideoPreview) window.initVideoPreview();
            }

            if (data.hasMore) {
                button.dataset.nextPage = String(data.nextPage);
                button.disabled = false;
                button.classList.remove('loading');
            } else {
                button.remove();
            }
        } catch (error) {
            button.disabled = false;
            button.classList.remove('loading');
        }
    });
}

document.addEventListener('DOMContentLoaded', initShowMoreVideos);
