// import { createInertiaApp } from '@inertiajs/vue3';
// import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// import type { DefineComponent } from 'vue';
// import { createApp, h } from 'vue';
// import { ZiggyVue } from 'ziggy-js';
// import { initializeTheme } from './composables/useAppearance';
import './adCarousel.js';
import './search-modal.js';
import './sticky-header.js';
import './player-ad-overlay.js';
import './ads.js';

// const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// createInertiaApp({
//     title: (title) => (title ? `${title} - ${appName}` : appName),
//     resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
//     setup({ el, App, props, plugin }) {
//         createApp({ render: () => h(App, props) })
//             .use(plugin)
//             .use(ZiggyVue)
//             .mount(el);
//     },
//     progress: {
//         color: '#4B5563',
//     },
// });

function isMobile() {
    return /Android|webOS|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// [video-preview] : Gère le chargement dynamique des images de prévisualisation au survol
function initVideoPreview() {
    const videoThumbnails = document.querySelectorAll('.cardVideo-image[data-preview-url]');
    // console.log('Vidéos détectées pour preview:', Array.from(videoThumbnails).map(thumb => ({
    //   previewUrl: thumb.getAttribute('data-preview-url'),
    //   alt: thumb.getAttribute('alt')
    // })));
    
    // Cache pour éviter les requêtes DOM répétées
    const thumbnailCache = new Map();
    
    videoThumbnails.forEach((thumbnail) => {
        const previewUrl = thumbnail.getAttribute('data-preview-url');
        
        if (!previewUrl) return;
        
        // Cache des éléments DOM pour cette thumbnail
        const container = thumbnail.parentElement;
        const preloadLine = thumbnail.closest('.cardVideo-image-container')?.querySelector('.preloadLine');
        
        // Stocker les références pour éviter les requêtes répétées
        const cacheData = {
            container,
            preloadLine,
            previewUrl,
            previewImage: null as Element | HTMLImageElement | null
        };
        thumbnailCache.set(thumbnail, cacheData);
                
        // Fonction optimisée pour créer l'image de prévisualisation
        function createPreviewImage() {
            if (!cacheData) return null;
            
            // Vérifier si une image existe déjà
            const existingPreview = cacheData.container?.querySelector('.cardVideo-image.preview');
            if (existingPreview) {
                cacheData.previewImage = existingPreview;
                return existingPreview;
            }
            
            // Créer la nouvelle image
            const previewImage = new Image();
            previewImage.src = previewUrl || '';
            previewImage.className = 'cardVideo-image preview';
            previewImage.style.cssText = `display: flex;`;
            
            // Vérifier si le container est déjà l'élément picture
            if (cacheData.container?.tagName.toLowerCase() === 'picture') {
                cacheData.container?.insertBefore(previewImage, cacheData.container?.firstChild);
            } else {
                // Sinon chercher l'élément picture
                const pictureElement = cacheData.container?.querySelector('picture');
                if (pictureElement) {
                    pictureElement.insertBefore(previewImage, pictureElement.firstChild);
                } else {
                    // Fallback au cas où picture n'existe pas
                    cacheData.container?.appendChild(previewImage);
                }
            }
            cacheData.previewImage = previewImage;
            
            return previewImage;
        }
        
        // Fonction optimisée pour afficher la prévisualisation
        function showPreview() {
            if (!cacheData) return;
            
            // Animer la ligne de préchargement
            if (cacheData.preloadLine) {
                (cacheData.preloadLine as HTMLElement).style.opacity = '1';
                (cacheData.preloadLine as HTMLElement).style.width = '100%';
                
                // Déclencher l'animation CSS sur mobile
                if (isMobile()) {
                    (cacheData.preloadLine as HTMLElement).style.animation = 'preloadAnimation linear .8s';
                    (cacheData.preloadLine as HTMLElement).style.animationIterationCount = '1';
                }
            }
            
            const preview = createPreviewImage();
            if (!preview) return;
            
            // Afficher l'image
            if ((preview as HTMLImageElement).complete) {
                (preview as HTMLImageElement).style.display = 'flex';
            } else {
                (preview as HTMLImageElement).onload = () => {
                    (preview as HTMLImageElement).style.display = 'flex';
                };
            }
        }
        
        // Fonction optimisée pour masquer la prévisualisation
        function hidePreview() {
            if (!cacheData) return;
            
            // Réinitialiser la ligne de préchargement
            if (cacheData.preloadLine) {
                (cacheData.preloadLine as HTMLElement).style.opacity = '0';
                (cacheData.preloadLine as HTMLElement).style.width = '0px';
  
                // Retirer l'animation CSS sur mobile
                if (isMobile()) {
                    (cacheData.preloadLine as HTMLElement).style.animation = '';
                    (cacheData.preloadLine as HTMLElement).style.animationIterationCount = '';
                }
            }
            
            // Masquer l'image
            if (cacheData.previewImage) {
                (cacheData.previewImage as HTMLElement).style.display = 'none';
            }
        }
        
        // Event listeners avec debouncing pour éviter les appels multiples
        let showTimeout: any;
        let hideTimeout: any;
        
        // Gestion des événements souris (desktop)
        container?.addEventListener('mouseenter', () => {
            clearTimeout(hideTimeout);
            showTimeout = setTimeout(showPreview, 50); // Délai de 50ms
        });
        
        container?.addEventListener('mouseleave', () => {
            clearTimeout(showTimeout);
            hideTimeout = setTimeout(hidePreview, 100); // Délai de 100ms
        });
        
        // Gestion des événements tactiles (mobile)
        if (isMobile()) {
            let isDown = false;
            let startY: any;
            let hasMoved = false;
            
            container?.addEventListener('touchstart', (e) => {
                isDown = true;
                hasMoved = false;
                startY = e.touches[0].pageY;
                clearTimeout(hideTimeout);
            });
            
            container?.addEventListener('touchmove', (e) => {
                if (!isDown) return;
                
                const currentY = e.touches[0].pageY;
                const dist = Math.abs(currentY - startY);
                
                // Si on a bougé significativement, afficher la preview
                if (dist > 3) {
                    hasMoved = true;
                    // Désactiver les liens pendant le scroll
                    container?.querySelectorAll('a').forEach(link => {
                        link.classList.add('disabled-link');
                    });
                    
                    // Afficher la preview
                    showPreview();
                    
                    // Masquer toutes les autres previews
                    document.querySelectorAll('.cardVideo-image.preview').forEach(preview => {
                        if (preview !== cacheData.previewImage) {
                            (preview as HTMLElement).style.display = 'none';
                        }
                    });
                }
            });
            
            container?.addEventListener('touchend', (e) => {
                isDown = false;
                
                // Réactiver les liens
                container?.querySelectorAll('a').forEach(link => {
                    link.classList.remove('disabled-link');
                });
                
                // Si pas de mouvement, c'était un click (pas de preview)
                if (!hasMoved) {
                    // Laisser le click naturel se faire
                }
            });
            
            // Gestionnaire global pour masquer la preview quand on touche ailleurs
            document.addEventListener('touchstart', (e) => {
                // Si on touche en dehors de ce container, masquer la preview
                if (!container?.contains(e.target as Node)) {
                    hidePreview();
                }
            }, { passive: true });
        }
    });
}

// Fonction pour calculer le temps écoulé (avec traductions)
function timeAgo(timestamp: number): string {
    const date = new Date(timestamp * 1000);
    const now = new Date();

    const diffInMs = now.getTime() - date.getTime();
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
    const diffInWeeks = Math.floor(diffInDays / 7);
    const diffInMonths = Math.floor(diffInDays / 30);
    const diffInYears = Math.floor(diffInDays / 365);

    // Fonction de traduction (window.t défini dans js-translations.blade.php)
    const t = (window as any).t || ((key: string) => key);

    if (diffInDays < 1) return t('time.today');
    if (diffInDays < 2) return t('time.yesterday');
    if (diffInDays < 7) return t(diffInDays === 1 ? 'time.day_ago' : 'time.days_ago').replace('{count}', String(diffInDays));
    if (diffInWeeks < 4) return t(diffInWeeks === 1 ? 'time.week_ago' : 'time.weeks_ago').replace('{count}', String(diffInWeeks));
    if (diffInMonths < 12) return t(diffInMonths === 1 ? 'time.month_ago' : 'time.months_ago').replace('{count}', String(diffInMonths));
    return t(diffInYears === 1 ? 'time.year_ago' : 'time.years_ago').replace('{count}', String(diffInYears));
}

// Fonction pour formater la durée
function formatDuration(seconds: number) {
    if (!seconds) return '00:00';
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;
  
    if (hours > 0) {
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
    }
    
    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

function formatElements(element = document.body) {
    const videoDurations = element.querySelectorAll('#video-duration');
    if (videoDurations.length > 0) {
        videoDurations.forEach(element => {
            const htmlElement = element as HTMLElement;
            const durationStr = htmlElement.dataset.duration;
            if (durationStr) {
                const duration = parseInt(durationStr);
                htmlElement.textContent = formatDuration(duration);
            }
        });
    }

    const sinceDates = element.querySelectorAll('[data-published]');
    if (sinceDates.length > 0) {
        sinceDates.forEach(element => {
            const htmlElement = element as HTMLElement;
            const publishedStr = htmlElement.dataset.published;
            if (publishedStr) {
                const timestamp = parseInt(publishedStr);
                const time = timeAgo(timestamp);
                htmlElement.textContent = time;

                // Ajouter le badge "New" pour les vidéos de moins de 24h
                const diffInMs = new Date().getTime() - (timestamp * 1000);
                const hours = Math.floor(diffInMs / (1000 * 60 * 60));

                if (hours < 24 && !htmlElement.closest('.cardVideo-image-container')) {
                    htmlElement.classList.add('date-new');
                    const infosDiv = htmlElement.closest('.cardVideo')?.querySelector('.cardVideo-image-container');
                    if (infosDiv && !infosDiv.querySelector('.cardVideo-badge')) {
                        const t = (window as any).t || ((key: string) => key);
                        const newBadge = document.createElement('a');
                        newBadge.href = '#';
                        newBadge.className = 'cardVideo-badge';
                        newBadge.textContent = t('time.new');
                        infosDiv.insertBefore(newBadge, infosDiv.firstChild);
                    }
                }
            }
        });
    }
}
// This will set light / dark mode on page load...
// initializeTheme();


// Définit le bouton de navigation actif selon la page courante
function setActiveButton() {
    // Gestion de la navigation active
    const currentUrl = window.location.pathname;

    const selectors = ['.menu-link', '.tabbar-btn'];
    
    selectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(link => {
        const linkHref = link.getAttribute("href");
        
        // Ignorer les éléments sans href (comme les boutons)
        if (!linkHref) return;
        
        // Extraire seulement le chemin de l'URL complète
        const linkPath = new URL(linkHref, window.location.origin).pathname;
        
        // Comparaison exacte d'abord
        if (linkPath === currentUrl) {
            link.setAttribute("aria-current", "page");
            link.classList.add('active');
        } else {
            // Comparaison pour les routes avec paramètres
            // Par exemple: /categories/some-category devrait matcher /categories
            const currentPath = currentUrl.split('?')[0];
            
            // Cas spécial pour la page d'accueil: active pour "/" et "/page"
            if (linkPath === '/' && currentPath.startsWith('/page')) {
                link.setAttribute("aria-current", "page");
                link.classList.add('active');
            } else if (linkPath && currentPath.startsWith(linkPath) && linkPath !== '/') {
                link.setAttribute("aria-current", "page");
                link.classList.add('active');
            } else {
                link.removeAttribute("aria-current");
                link.classList.remove('active');
                }
            }
        });
    });
}

// [menu, tabbar-btn-active] : Initialise le menu mobile avec ses interactions
function initMenu() {
    const menuBtn = document.querySelector('.megamenu-btn');
    const menu = document.querySelector('.menu');
    
    if (!menuBtn || !menu) return;
    
    function toggleMenuState(isOpen: boolean) {
        if (isOpen && menu) {
            menu.classList.add('open');
            document.body.classList.add('menu-open');
        } else {
            menu?.classList.remove('open');
            document.body.classList.remove('menu-open');
        }
        // Changer l'état du bouton
        menuBtn?.setAttribute('aria-expanded', isOpen.toString());
    }
    
    menuBtn?.addEventListener('click', (e: Event) => {
        e.preventDefault();
        const isMenuOpen = menu?.classList.contains('open');
        toggleMenuState(!isMenuOpen);
    });
    
    // Fermer le menu en cliquant en dehors
    menu?.addEventListener('click', (e: Event) => {
        if (e.target === menu) {
            toggleMenuState(false);
        }
    });
    
    // Fermer le menu avec la touche Escape
    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape' && menu?.classList.contains('open')) {
            toggleMenuState(false);
        }
    });
}


document.addEventListener('DOMContentLoaded', async () => {
    initVideoPreview();
    formatElements();
    setActiveButton();
    initMenu();
});