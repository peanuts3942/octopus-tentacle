const videoAdCardTemplate = (type, ad_content, href, alt, title, avatar) => {
  let ad = `
      <a class="cardVideo-image-container trans" href="https://go.rmhfrtnd.com?campaignId=video%20card%20ads&creativeId=thumbnail%20cam&sourceId=https%3A%2F%2Fborntobefuck.com&targetDomain=borntobefuck.cam&action=showTokensGiveawayModalDirectLink&userId=6768ea4dbd3db19f710e6a56342b76c4da884392a07668eacd6371ebb32d43e6" rel="nofollow noopener" target="_blank">
          <img src="/images/default-thumbnail.jpg" alt="Promote your business with us" class="cardVideo-image">
      </a>`
  if (type === 'image'){
      ad = `
      <a class="cardVideo-image-container trans" href="${href}" rel="nofollow noopener" target="_blank">
          <img src="${ad_content}" alt="${alt}" class="cardVideo-image">
      </a>`
  }
  if (type === 'iframe'){
      ad = ad_content
  }

  title = title ?? "Promote Your Business with us Ads and Reach 3M+ Active Users!";
  // avatar = avatar ?? "/images/favicon-borntobefuck.png";

  const html = `
  <div class="cardVideo">
      ${ad}
      <a class="cardVideo-infos" href="mailto:ads@tanaleak.com" rel="nofollow noopener" target="_blank">
        <div class="cardVideo-title trans">
            <h3>${title}</h3>
        </div>
        <div class="cardVideo-date-channel-container">
            <p class="cardVideo-date">Contact us</p>
            <span class="cardVideo-separator">•</span>
            <p class="cardVideo-channel trans">ads@tanaleak.com</p>
        </div>
      </a>
  </div>`;
  return html;
};

async function generateVideoAdCardHtml(adSpot){
  try {
      // Récupérer les paramètres depuis l'API
      const feedVideoAdCardSettings = await fetchFeedVideoAdCardSettings();
      
      // Vérifier si les items existent
      if (!feedVideoAdCardSettings.options || !feedVideoAdCardSettings.options.items || feedVideoAdCardSettings.options.items.length === 0) {
          console.warn('Aucun item trouvé dans les paramètres feed-video-ad-card');
          // Retourner une publicité par défaut
          return videoAdCardTemplate('image', '/images/default-thumbnail.jpg', 'https://go.rmhfrtnd.com?campaignId=video%20card%20ads&creativeId=thumbnail%20cam&sourceId=https%3A%2F%2Fborntobefuck.com&targetDomain=borntobefuck.cam&action=showTokensGiveawayModalDirectLink&userId=6768ea4dbd3db19f710e6a56342b76c4da884392a07668eacd6371ebb32d43e6', 'Promote your business with us');
      }
      
              const videoAdCards = feedVideoAdCardSettings.options.items;

      // DEBUG: Log des données reçues
      // console.log(`[DEBUG] generateVideoAdCardHtml - adSpot: ${adSpot}`);
      // console.log(`[DEBUG] generateVideoAdCardHtml - videoAdCards disponibles:`, videoAdCards);
      
      // Sélectionner un videoAdCard selon la logique métier
      const selectedItem = selectVideoAdCardBySpot(videoAdCards, adSpot);
      
      // DEBUG: Log du résultat de sélection
      // console.log(`[DEBUG] generateVideoAdCardHtml - selectedItem:`, selectedItem);
      
      if (!selectedItem) {
          console.warn(`Aucun videoAdCard trouvé pour le spot ${adSpot}, utilisation de la publicité par défaut`);
          return videoAdCardTemplate('image', '/images/default-thumbnail.jpg', 'https://go.rmhfrtnd.com?campaignId=video%20card%20ads&creativeId=thumbnail%20cam&sourceId=https%3A%2F%2Fborntobefuck.com&targetDomain=borntobefuck.cam&action=showTokensGiveawayModalDirectLink&userId=6768ea4dbd3db19f710e6a56342b76c4da884392a07668eacd6371ebb32d43e6', 'Promote your business with us');
      }
      
      // Extraire les paramètres de l'item sélectionné
      const { type, ad_content, href, alt, title, avatar } = selectedItem;
      
      // Générer le HTML avec les paramètres de l'item sélectionné
      return videoAdCardTemplate(type, ad_content, href, alt, title, avatar);
      
  } catch (error) {
      console.error('Erreur lors de la génération de la publicité vidéo:', error);
      // Retourner une publicité par défaut en cas d'erreur
      return videoAdCardTemplate('image', '/images/default-thumbnail.jpg', 'https://go.rmhfrtnd.com?campaignId=video%20card%20ads&creativeId=thumbnail%20cam&sourceId=https%3A%2F%2Fborntobefuck.com&targetDomain=borntobefuck.cam&action=showTokensGiveawayModalDirectLink&userId=6768ea4dbd3db19f710e6a56342b76c4da884392a07668eacd6371ebb32d43e6', 'Promote your business with us');
  }
}

/**
* Sélectionne un videoAdCard selon le spot et la logique de fréquence
* @param {Array} videoAdCards - Liste des videoAdCards disponibles
* @param {number} adSpot - Le spot actuel (1-4)
* @returns {Object|null} Le videoAdCard sélectionné ou null si aucun trouvé
*/
function selectVideoAdCardBySpot(videoAdCards, adSpot) {
  // console.log(`[DEBUG] selectVideoAdCardBySpot - Recherche pour adSpot: ${adSpot}`);
  // console.log(`[DEBUG] selectVideoAdCardBySpot - Total videoAdCards: ${videoAdCards.length}`);
  
  // D'abord essayer de trouver des videoAdCards pour le spot spécifique
  const spotSpecificCards = videoAdCards.filter(card => card.spot === adSpot);
  // console.log(`[DEBUG] selectVideoAdCardBySpot - Cartes trouvées pour spot ${adSpot}:`, spotSpecificCards);
  
  if (spotSpecificCards.length > 0) {
      // console.log(`[DEBUG] selectVideoAdCardBySpot - Utilisation des cartes spécifiques au spot ${adSpot}`);
      return selectByFrequency(spotSpecificCards);
  }
  
  // Fallback : sélectionner parmi les videoAdCards sans spot assigné
  const fallbackCards = videoAdCards.filter(card => card.spot === null);
  // console.log(`[DEBUG] selectVideoAdCardBySpot - Fallback: cartes sans spot (null):`, fallbackCards);
  
  if (fallbackCards.length > 0) {
      // console.log(`[DEBUG] selectVideoAdCardBySpot - Utilisation du fallback avec cartes sans spot`);
      return selectByFrequency(fallbackCards);
  }
  
  // console.log(`[DEBUG] selectVideoAdCardBySpot - Aucune carte trouvée, retour null`);
  return null;
}

/**
* Sélectionne un videoAdCard en respectant la fréquence pondérée
* @param {Array} videoAdCards - Liste des videoAdCards avec leurs fréquences
* @returns {Object} Le videoAdCard sélectionné
*/
function selectByFrequency(videoAdCards) {
  // console.log(`[DEBUG] selectByFrequency - Début de sélection pondérée`);
  // console.log(`[DEBUG] selectByFrequency - Cartes à traiter:`, videoAdCards);
  
  // Générer un nombre aléatoire entre 1 et 100
  const random = Math.floor(Math.random() * 100) + 1;
  // console.log(`[DEBUG] selectByFrequency - Nombre aléatoire généré: ${random}`);
  
  let cumulativeFrequency = 0;
  
  for (const card of videoAdCards) {
      cumulativeFrequency += card.frequence;
      // console.log(`[DEBUG] selectByFrequency - Carte: ${card.alt || 'sans nom'}, Fréquence: ${card.frequence}, Cumul: ${cumulativeFrequency}`);
      
      if (random <= cumulativeFrequency) {
          // console.log(`[DEBUG] selectByFrequency - Carte sélectionnée:`, card);
          return card;
      }
  }
  
  // Fallback : retourner le premier si jamais atteint (normalement impossible)
  // console.log(`[DEBUG] selectByFrequency - Fallback: utilisation de la première carte`);
  return videoAdCards[0];
}

function fetchFeedVideoAdCardSettings(){
  return new Promise((resolve, reject) => {
      // Vérifier d'abord le localStorage
      const cachedData = getCachedSettings('feed-video-ad-card');
      if (cachedData) {
          resolve(cachedData);
          return;
      }

      // Faire la requête AJAX si pas en cache
      fetch('/application-settings/feed-video-ad-card', {
          method: 'GET',
          headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/json',
              'Accept': 'application/json'
          }
      })
      .then(response => {
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
      })
      .then(data => {
          if (data.success) {
              // Stocker dans localStorage avec TTL
              setCachedSettings('feed-video-ad-card', data.data, 10);
              resolve(data.data);
          } else {
              reject(new Error(data.message || 'Erreur lors de la récupération des paramètres'));
          }
      })
      .catch(error => {
          console.error('Erreur lors de la récupération des paramètres:', error);
          reject(error);
      });
  });
}

/**
* Stocke les données dans localStorage avec un TTL
* @param {string} key - Clé de stockage
* @param {object} data - Données à stocker
* @param {number} ttlMinutes - Durée de vie en minutes
*/
function setCachedSettings(key, data, ttlMinutes) {
  try {
      const cacheData = {
          data: data,
          timestamp: Date.now(),
          ttl: ttlMinutes * 60 * 1000 // Convertir en millisecondes
      };
      localStorage.setItem(`ads_settings_${key}`, JSON.stringify(cacheData));
  } catch (error) {
      console.error('Erreur lors du stockage en localStorage:', error);
  }
}

/**
* Récupère les données du localStorage avec vérification du TTL
* @param {string} key - Clé de stockage
* @returns {object|null} - Données ou null si expirées/inexistantes
*/
function getCachedSettings(key) {
  try {
      const cached = localStorage.getItem(`ads_settings_${key}`);
      if (!cached) {
          return null;
      }

      const cacheData = JSON.parse(cached);
      const now = Date.now();
      const isExpired = (now - cacheData.timestamp) > cacheData.ttl;

      if (isExpired) {
          // Supprimer les données expirées
          localStorage.removeItem(`ads_settings_${key}`);
          return null;
      }

      return cacheData.data;
  } catch (error) {
      console.error('Erreur lors de la récupération du localStorage:', error);
      return null;
  }
}

// [video-grid-ads] Insert ad placeholders every N videos (configurable) and render ads
function initVideoGridAds() {
  try {
    const grids = document.querySelectorAll('.videos-grid');
    if (!grids || grids.length === 0) return;

    grids.forEach((grid) => {
      const gridElement = grid;
      if (gridElement.getAttribute('data-ads-inserted') === 'true') return;

      // Determine interval: default 6, can be overridden per grid via data-ad-interval
      const defaultInterval = 6;
      const intervalAttr = gridElement.getAttribute('data-ad-interval');
      let interval = defaultInterval;
      if (intervalAttr) {
        const parsed = parseInt(intervalAttr, 10);
        if (!isNaN(parsed) && parsed > 0) {
          interval = parsed;
        }
      }

      // Only consider direct children cards at initial state
      const cards = Array.from(gridElement.querySelectorAll(':scope > .cardVideo'));
      if (cards.length === 0) {
        gridElement.setAttribute('data-ads-inserted', 'true');
        return;
      }

      let adSlotCount = 0;

      cards.forEach((card, index) => {
        const position = index + 1;
        if (position % interval === 0) {
          adSlotCount += 1;
          const spot = ((adSlotCount - 1) % 4) + 1; // cycle 1..4

          const placeholder = document.createElement('article');
          placeholder.className = 'cardVideo ad-placeholder';
          placeholder.setAttribute('data-ad-spot', String(spot));
          placeholder.innerHTML = '<div class="video ad loading"><div class="top"></div><a class="bottom" href="#"><div class="details"><p class="video-title">Loading…</p></div></a></div>';

          if (card.nextSibling) {
            gridElement.insertBefore(placeholder, card.nextSibling);
          } else {
            gridElement.appendChild(placeholder);
          }

          // Render ad content asynchronously
          generateVideoAdCardHtml(spot)
            .then((html) => {
              placeholder.outerHTML = html;
            })
            .catch(() => {
              // On error, keep placeholder minimal or remove
              placeholder.remove();
            });
        }
      });

      gridElement.setAttribute('data-ads-inserted', 'true');
    });
  } catch (e) {
    console.error('[Ads] Failed to initialize video grid ads:', e);
  }
}

// Auto-init on DOMContentLoaded
// document.addEventListener('DOMContentLoaded', () => {
//   initVideoGridAds();
// });