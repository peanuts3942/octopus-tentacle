/**
 * Gestionnaire de données pour le carousel
 */
class AdCarouselDataManager {
    constructor(element) {
        this.element = element;
    }

    loadData() {
        try {
            const randomData = this.element.getAttribute('data-random');
            const showOnlyOneRandomlyData = this.element.getAttribute('data-show-only-one-randomly');
            
            const random = randomData ? JSON.parse(randomData) : false;
            const showOnlyOneRandomly = showOnlyOneRandomlyData ? JSON.parse(showOnlyOneRandomlyData) : false;
            
            return { random, showOnlyOneRandomly };
        } catch (error) {
            console.error('Erreur lors du chargement des données:', error);
            return null;
        }
    }

    selectRandomElement(elements) {
        if (!elements || elements.length === 0) return null;
        const randomIndex = Math.floor(Math.random() * elements.length);
        return elements[randomIndex];
    }

    shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }
}

/**
 * Gestionnaire d'animation pour le carousel
 */
class AdCarouselAnimator {
    constructor(element) {
        this.element = element;
        this.currentSlide = 0;
        this.interval = null;
        this.isInitialized = false;
    }

    initialize() {
        const slides = this.element.querySelectorAll('.ad-carousel a');
        
        if (!slides.length || slides.length <= 1) {
            if (slides.length === 1) {
                slides[0].classList.add('active');
                slides[0].style.transform = 'translateX(0)';
            }
            return;
        }

        // Vérifier que toutes les images sont chargées avant d'initialiser
        this.waitForImagesLoaded(slides).then(() => {
            this.currentSlide = 0;
            this.isInitialized = true;

            // S'assurer que la première slide soit visible immédiatement
            slides.forEach((slide, index) => {
                if (index === 0) {
                    slide.style.transform = 'translateX(0)';
                    slide.classList.add('active');
                } else {
                    slide.style.transform = 'translateX(100%)';
                    slide.classList.remove('active');
                }
            });

            // Démarrer l'animation après un court délai pour éviter le flash initial
            setTimeout(() => {
                this.start();
            }, 100);
        });
    }

    waitForImagesLoaded(slides) {
        const imagePromises = Array.from(slides).map(slide => {
            const img = slide.querySelector('img');
            if (img && !img.complete) {
                return new Promise((resolve) => {
                    img.onload = resolve;
                    img.onerror = resolve; // Continuer même si l'image échoue
                });
            }
            return Promise.resolve();
        });
        
        return Promise.all(imagePromises);
    }

    start() {
        if (this.interval) {
            clearInterval(this.interval);
        }

        this.interval = setInterval(() => {
            this.nextSlide();
        }, 5000);
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    nextSlide() {
        const slides = this.element.querySelectorAll('.ad-carousel a');
        if (!slides.length) return;

        const nextSlide = (this.currentSlide + 1) % slides.length;
        
        // Préparer la prochaine slide à droite
        slides[nextSlide].style.transform = 'translateX(100%)';
        slides[nextSlide].classList.remove('active');
        
        // Retirer les transitions pour le repositionnement instantané
        slides.forEach(slide => {
            slide.style.transition = 'none';
        });
        
        // Forcer le navigateur à appliquer les styles sans transition
        void slides[0].offsetWidth;
        
        // Réactiver les transitions
        slides.forEach(slide => {
            slide.style.transition = 'transform 0.5s ease-in-out';
        });
        
        // Animer les slides
        slides[this.currentSlide].style.transform = 'translateX(-100%)';
        slides[this.currentSlide].classList.remove('active');
        slides[nextSlide].style.transform = 'translateX(0)';
        slides[nextSlide].classList.add('active');
        
        this.currentSlide = nextSlide;
    }

    play() {
        if (this.isInitialized) {
            this.start();
        }
    }

    pause() {
        this.stop();
    }
}

/**
 * Web Component principal simplifié
 */
class AdCarousel extends HTMLElement {
    constructor() {
        super();
        this.dataManager = new AdCarouselDataManager(this);
        this.animator = new AdCarouselAnimator(this);
    }

    connectedCallback() {
        this.initialize();
    }

    disconnectedCallback() {
        this.animator.stop();
    }

    initialize() {
        const data = this.dataManager.loadData();
        
        if (!data) {
            this.renderError();
            return;
        }

        const { random, showOnlyOneRandomly } = data;
        
        // Gérer show_only_one_randomly
        if (showOnlyOneRandomly) {
            this.showOnlyOneRandomly();
            // Ne pas initialiser l'animateur en mode "une seule pub"
            return;
        } else {
            // Mélanger les slides si random est activé
            if (random) {
                this.shuffleSlides();
            }
        }

        this.animator.initialize();
    }

    showOnlyOneRandomly() {
        // Sélectionner une seule slide au hasard parmi les éléments HTML existants
        const slides = this.querySelectorAll('.ad-carousel a');
        const selectedSlide = this.dataManager.selectRandomElement(slides);
        
        if (selectedSlide) {
            // Cacher toutes les slides
            slides.forEach(slide => {
                slide.style.display = 'none';
            });
            
            // Afficher seulement la slide sélectionnée
            selectedSlide.style.display = 'block';
            selectedSlide.classList.add('active');
            selectedSlide.style.transform = 'translateX(0)';
        }
    }

    shuffleSlides() {
        const slides = Array.from(this.querySelectorAll('.ad-carousel a'));
        
        // Mélanger l'ordre des slides dans le DOM
        const container = this.querySelector('.ad-carousel');
        const shuffled = this.dataManager.shuffleArray(slides);
        
        shuffled.forEach(slide => {
            container.appendChild(slide);
        });
    }

    renderError() {
        this.innerHTML = `
            <div class="ad-carousel error">
                <p>Erreur de chargement des annonces</p>
            </div>
        `;
    }

    // Méthodes publiques pour contrôle externe
    play() {
        this.animator.play();
    }

    pause() {
        this.animator.pause();
    }

    updateData(sliders, random = false) {
        this.animator.stop();
        // Les données sont maintenant gérées côté Blade
        this.initialize();
    }
}

// Enregistrer le Web Component
customElements.define('ad-carousel', AdCarousel);
