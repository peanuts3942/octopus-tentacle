class PlayerAdOverlay extends HTMLElement {}

// Avoid double-definition errors with HMR or multiple imports
if (!customElements.get('player-ad-overlay')) {
  try {
    customElements.define('player-ad-overlay', PlayerAdOverlay);
  } catch (e) {
    // Ignore if already defined for any reason
  }
}