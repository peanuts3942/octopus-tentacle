class StickyHeader extends HTMLElement {
  constructor() {
      super();
      this.lastScrollY = window.scrollY;
      this.ticking = false;
      this.bannerHeight = 0;
      this.headerHeight = 0;
      this.animationType = this.getAttribute('data-animation-type') || 'css';
      this.scrollThreshold = 50; // Réduit le seuil pour une réponse plus rapide
      this.isHidden = false;
      
      // Propriétés pour le modal de recherche
      this.searchModal = null;
      this.isSearchOpen = false;
      
      // Vérifier si le sticky est désactivé
      this.noSticky = this.hasAttribute('noSticky');
  }

  connectedCallback() {
      // Récupérer la hauteur de la bannière avec les bordures
      const banner = document.querySelector('.banner');
      if (banner) {
          // const bannerRect = banner.getBoundingClientRect();
          this.bannerHeight = banner.offsetHeight;
      }
      
      // Récupérer la hauteur du header
      this.headerHeight = this.offsetHeight;
      
      // Initialiser le modal de recherche
      this.initializeSearchModal();
      
      // Ne pas ajouter les écouteurs de scroll si noSticky est activé
      if (this.noSticky) {
          return;
      }
      
      if (this.animationType === 'js') {
          window.addEventListener('scroll', this.handleScroll.bind(this));
      } else {
          this.classList.add('css-animation');
          window.addEventListener('scroll', this.handleCssAnimation.bind(this));
      }
  }

  disconnectedCallback() {
      // Ne pas supprimer les écouteurs si noSticky est activé (ils n'ont pas été ajoutés)
      if (this.noSticky) {
          this.cleanupSearchModal();
          return;
      }
      
      if (this.animationType === 'js') {
          window.removeEventListener('scroll', this.handleScroll.bind(this));
      } else {
          window.removeEventListener('scroll', this.handleCssAnimation.bind(this));
      }
      
      // Nettoyer les écouteurs d'événements du modal de recherche
      this.cleanupSearchModal();
  }

  initializeSearchModal() {
      // Récupérer les éléments du modal de recherche en ciblant st
      this.searchModal = document.querySelector('search-modal');
      
      if (this.searchModal) {
          // Ajouter l'écouteur pour le bouton de recherche
          document.addEventListener('click', this.handleSearchTrigger.bind(this));
          
          // Écouter les demandes de fermeture du modal
          this.searchModal.addEventListener('searchModal:requestClose', this.closeSearchModal.bind(this));
      }
  }

  cleanupSearchModal() {
      // Supprimer les écouteurs d'événements
      document.removeEventListener('click', this.handleSearchTrigger.bind(this));
      
      if (this.searchModal) {
          this.searchModal.removeEventListener('searchModal:requestClose', this.closeSearchModal.bind(this));
      }
  }

  handleSearchTrigger(e) {
      const trigger = e.target.closest('[open-search-modal]');
      if (trigger) {
          this.toggleSearchModal();
      }
  }

  toggleSearchModal() {
      this.isSearchOpen ? this.closeSearchModal() : this.openSearchModal();
  }

  openSearchModal() {
      if (this.searchModal) {
          this.isSearchOpen = true;
          this.searchModal.classList.add('open');
          document.body.classList.add('search-modal-open');
          
          // Déclencher l'événement d'ouverture pour que search-modal.js puisse gérer le focus
          this.searchModal.dispatchEvent(new CustomEvent('searchModal:open'));
      }
  }

  closeSearchModal() {
      if (this.searchModal) {
          this.isSearchOpen = false;
          this.searchModal.classList.remove('open');
          document.body.classList.remove('search-modal-open');
          
          // Déclencher l'événement de fermeture pour que search-modal.js puisse gérer le nettoyage
          this.searchModal.dispatchEvent(new CustomEvent('searchModal:close'));
      }
  }

  handleScroll() {
      if (!this.ticking) {
          window.requestAnimationFrame(() => {
              const currentScrollY = window.scrollY;
              
              // Calculer la position du header en fonction du scroll
              if (currentScrollY <= this.bannerHeight) {
                  // Quand on est dans la zone de la bannière, le header suit le scroll
                  const headerPosition = Math.max(0, this.bannerHeight - currentScrollY);
                  this.style.top = `${headerPosition}px`;
                  this.style.transform = 'translateY(0)';
              } else {
                  // En dehors de la zone de la bannière
                  this.style.top = '0';
                  
                  if (currentScrollY > this.lastScrollY) {
                      // Scroll vers le bas - calculer la translation en fonction du scroll
                      const scrollDelta = currentScrollY - this.lastScrollY;
                      const currentTransform = parseFloat(this.style.transform.replace('translateY(', '').replace('px)', '') || 0);
                      const newTransform = Math.max(-this.headerHeight, currentTransform - scrollDelta);
                      this.style.transform = `translateY(${newTransform}px)`;
                  } else {
                      // Scroll vers le haut - calculer la translation en fonction du scroll
                      const scrollDelta = this.lastScrollY - currentScrollY;
                      const currentTransform = parseFloat(this.style.transform.replace('translateY(', '').replace('px)', '') || -this.headerHeight);
                      const newTransform = Math.min(0, currentTransform + scrollDelta);
                      this.style.transform = `translateY(${newTransform}px)`;
                  }
              }
              
              this.lastScrollY = currentScrollY;
              this.ticking = false;
          });
          
          this.ticking = true;
      }
  }

  handleCssAnimation() {
      if (!this.ticking) {
          window.requestAnimationFrame(() => {
              const currentScrollY = window.scrollY;
              
              if (currentScrollY <= this.bannerHeight) {
                  // Dans la zone de la bannière
                  // this.style.top = `${this.bannerHeight - currentScrollY}px`;
                  this.classList.remove('hidden');
                  this.isHidden = false;
              } else {
                  // En dehors de la zone de la bannière
                  // this.style.top = '0';
                  
                  if (currentScrollY > this.lastScrollY && !this.isHidden) {
                      // Scroll vers le bas
                      this.classList.add('hidden');
                      this.classList.remove('show');
                      this.isHidden = true;
                  } else if (currentScrollY < this.lastScrollY && this.isHidden) {
                      // Scroll vers le haut
                      this.classList.remove('hidden');
                      this.classList.add('show');
                      this.isHidden = false;
                  }
              }
              
              this.lastScrollY = currentScrollY;
              this.ticking = false;
          });
          
          this.ticking = true;
      }
  }
}

customElements.define('sticky-header', StickyHeader);
