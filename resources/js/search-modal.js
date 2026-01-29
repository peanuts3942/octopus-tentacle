class SearchModal extends HTMLElement {

  constructor() {
      super();
      this.searchInput = null;
      this.deleteButton = null;
      this.slidersLoaded = false;
      this.slidersContainer = null;
  }

  connectedCallback() {
      this.initializeElements();
      this.initializeEventListeners();
  }

  disconnectedCallback() {
      this.cleanupEventListeners();
  }

  initializeElements() {
      this.searchInput = this.querySelector('.search-input');
      this.deleteButton = this.querySelector('.search-delete');
      this.slidersContainer = this.querySelector('.search-sliders-container');
      
      // Créer le conteneur des sliders s'il n'existe pas
      if (!this.slidersContainer) {
          this.slidersContainer = document.createElement('div');
          this.slidersContainer.className = 'search-sliders-container';
          this.slidersContainer.style.display = 'none';
          this.appendChild(this.slidersContainer);
      }
  }

  initializeEventListeners() {
      // Écouter les événements d'ouverture/fermeture du sticky-header
      this.addEventListener('searchModal:open', this.handleOpen.bind(this));
      this.addEventListener('searchModal:close', this.handleClose.bind(this));
      
      // Écouteur pour fermer avec Escape
      document.addEventListener('keydown', this.handleKeydown.bind(this));
      
      // Écouteur pour fermer en cliquant en dehors
      this.addEventListener('click', this.handleOutsideClick.bind(this));
      
      // Écouteur pour le bouton de suppression
      if (this.deleteButton) {
          this.deleteButton.addEventListener('click', this.clearSearchInput.bind(this));
      }
      
      // Écouteur pour gérer l'affichage du bouton delete
      if (this.searchInput) {
          this.searchInput.addEventListener('input', this.handleInput.bind(this));
          // Initialiser l'état du bouton delete
          this.updateDeleteButtonVisibility();
      }
      
      // Écouteur pour empêcher la soumission si l'input est vide
      const searchForm = this.querySelector('.search-form');
      if (searchForm) {
          searchForm.addEventListener('submit', this.handleSubmit.bind(this));
      }
  }

  cleanupEventListeners() {
      document.removeEventListener('keydown', this.handleKeydown.bind(this));
      
      if (this.deleteButton) {
          this.deleteButton.removeEventListener('click', this.clearSearchInput.bind(this));
      }
      
      if (this.searchInput) {
          this.searchInput.removeEventListener('input', this.handleInput.bind(this));
      }
      
      const searchForm = this.querySelector('.search-form');
      if (searchForm) {
          searchForm.removeEventListener('submit', this.handleSubmit.bind(this));
      }
  }

  handleOpen() {
      // Focus sur l'input avec un petit délai pour assurer l'animation
      setTimeout(() => {
          this.searchInput?.focus();
      }, 50);
      
      // Charger les sliders si pas encore fait
      if (!this.slidersLoaded) {
          this.loadSliders();
      }
  }

  handleClose() {
      // Ne pas réinitialiser l'input automatiquement lors de la fermeture
      // L'utilisateur peut vouloir rouvrir le modal avec le même contenu
      this.updateDeleteButtonVisibility();
  }

  handleKeydown(e) {
      if (e.key === 'Escape' && this.classList.contains('open')) {
          // Déclencher la fermeture via le sticky-header
          this.dispatchEvent(new CustomEvent('searchModal:requestClose'));
      }
  }

  handleOutsideClick(e) {
      if (e.target === this) {
          // Déclencher la fermeture via le sticky-header
          this.dispatchEvent(new CustomEvent('searchModal:requestClose'));
      }
  }

  handleInput() {
      this.updateDeleteButtonVisibility();
  }

  updateDeleteButtonVisibility() {
      if (this.deleteButton && this.searchInput) {
          const hasText = this.searchInput.value.trim().length > 0;
          this.deleteButton.style.display = hasText ? 'flex' : 'none';
      }
  }

  clearSearchInput() {
      if (this.searchInput) {
          this.searchInput.value = '';
          this.searchInput.focus();
          this.updateDeleteButtonVisibility();
      }
  }

  handleSubmit(e) {
      // Vérifier si l'input est vide
      if (!this.searchInput || this.searchInput.value.trim().length === 0) {
          e.preventDefault();
          return false;
      }
      
      // Si l'input n'est pas vide, laisser le formulaire se soumettre normalement
      return true;
  }
}

customElements.define('search-modal', SearchModal);
