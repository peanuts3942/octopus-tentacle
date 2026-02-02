<!-- data-vast-url="https://a.adtng.com/get/10016243" -->
<player-ad-overlay 
    data-vast-url="https://a.adtng.com/get/10016243"
    data-max-popups="0"
    data-initial-delay="1000"
    data-popup-initial-delay="1"
    data-delay-between-popups="1"
    data-random="false"
    data-popup-urls='[]'
>
    <div class="player-container container-overlay" style="position: relative;">
        {{ $slot }}
    </div>

    <style>
        player-ad-overlay {
            display: block;
            position: relative;
        }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: '{{ theme('font_family') }}', sans-serif;
        }
        /* .player-container {
            position: relative;
            aspect-ratio: 16/9 !important;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #000;
            overflow: hidden;
        } */
        /* Style VAST Player */
        .vast-container {
            position: absolute;
            top: 0;
            aspect-ratio: 16 / 9 !important;
            height: 100%;
            background: #000;
            z-index: 50;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .vast-container .video-js {
            width: 100%;
            height: 100%;
        }
        .vast-skip-btn {
          position: absolute;
          bottom: 20px;
          right: 20px;
          background-color: #0009;
          backdrop-filter: blur(10px);
          -webkit-backdrop-filter: blur(10px);
          color: white;
          border: none;
          padding: 10px 16px;
          z-index: 10;
          border-radius: 100px;
          font-size: 12px;
          font-weight: 900;
          display: flex;           /* ← tjs visible */
          align-items: center;
          gap: 6px;
        }
        .vast-skip-btn[disabled],
        .vast-skip-btn.is-disabled {
          opacity: .6;
          pointer-events: none;    /* évite les clics fantômes */
        }
        .vast-timer {
            position: absolute;
            bottom: 25px;
            left: 20px;
            background: rgba(0, 0, 0, 0.0);
            color: white;
            font-size: 14px;
            font-weight: 800;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
            z-index: 10;
        }
        .vast-progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 5px;
            background-color: #E85D04;
            width: 0%;
            z-index: 15;
        }
        .vast-click-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 5;
            display: none;
        }
        .vast-hidden{ opacity:0; pointer-events:none; }

        /* On masque aussi le texte debug interne */
        .vjs-tech:before {
        content: "" !important;
        }
          /* Spinner au centre du conteneur VAST */
        .vast-spinner {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            z-index: 20;
        }
        .vast-spinner::after {
            content: "";
            width: 42px; height: 42px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,.35);
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Timer/Progress cachés par défaut */
        .vast-timer, .vast-progress-bar { display: none; }
    </style>

    <script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>
    <script>
    // --- Hub global: une seule capture de geste pour toutes les instances ---
    (function initGlobalGestureHub(){
      if (window.__PAO_HUB__) return;
      const instances = new Set();
      let interacted = false;

      // on notifie toutes les instances
      function notifyAll(){
        interacted = true;
        for (const inst of instances) { try { inst.__onFirstUserGesture(); } catch(e){} }
      }

      // capture "synchrone" sur un maximum d'événements (mobile + desktop)
      const ctl = new AbortController();
      const sig = ctl.signal;
      ['pointerdown','mousedown','touchstart','touchend','wheel','scroll','keydown']
        .forEach(ev => window.addEventListener(ev, () => {
          if (!interacted) notifyAll();
          // on n'aborte PAS: d'autres pages/iframes peuvent en avoir besoin
        }, { passive:true, signal:sig }));

      window.__PAO_HUB__ = {
        register: (inst)=>instances.add(inst),
        unregister: (inst)=>instances.delete(inst),
        hasInteracted: ()=>interacted
      };
    })();

    // ---------------- Composant ----------------
    class PlayerAdOverlay extends HTMLElement {
      connectedCallback() {

        // --- config
        this.vastUrl = this.dataset.vastUrl;
        this.maxPopups = parseInt(this.dataset.maxPopups || "0", 10);
        this.initialDelay = parseInt(this.dataset.initialDelay || "0", 10);
        this.delayBetweenPopups = parseInt(this.dataset.delayBetweenPopups || "0", 10);
        this.popupInitialDelay = parseInt(this.dataset.popupInitialDelay || "0", 10);
        this.random = this.dataset.random === "true";
        this.popupUrls = JSON.parse(this.dataset.popupUrls || "[]");
        if (this.random) this.popupUrls.sort(() => Math.random() - 0.5);
        this.container = this.querySelector('.container-overlay');

        // état
        this.vjsPlayer = null;
        this._wantUnmute = window.__PAO_HUB__.hasInteracted(); // si déjà interagi avant le composant
        window.__PAO_HUB__.register(this);

        // préfetch non bloquant
        this.prefetched = null;
        this.prefetchVAST(this.vastUrl, 10000).catch(()=>{});

        // lance VAST
        setTimeout(() => this.launchVASTThenPopups(), this.initialDelay);
      }

      disconnectedCallback(){
        try { window.__PAO_HUB__.unregister(this); } catch(e){}
      }

      // appelé par le hub au 1er geste utilisateur
      __onFirstUserGesture(){
        this._wantUnmute = true;
        // si le player est déjà là → démuter synchrone
        if (this.vjsPlayer) this.__attemptUnmuteNow(this.vjsPlayer);
      }

      __attemptUnmuteNow(player){
        try {
          // IMPORTANT: faire ces appels dans le handler du geste (ou synchronement après)
          player.muted(false);
          player.volume(1);
          const p = player.play();
          if (p && p.catch) p.catch(() => {
            // si refus (rare après geste), on revient en muted et on garde la lecture
            player.muted(true);
            player.play();
          });
        } catch(e){
          player.muted(true);
          try { player.play(); } catch(_){}
        }
      }

      async prefetchVAST(vastUrl, timeoutMs) {
        const ctl = new AbortController();
        const t = setTimeout(()=>ctl.abort('timeout'), timeoutMs);
        try {
          const res = await fetch(vastUrl, { signal: ctl.signal });
          if (!res.ok) throw new Error('VAST fetch failed');
          const xml = new DOMParser().parseFromString(await res.text(), "application/xml");

          const mediaNodes = Array.from(xml.querySelectorAll("MediaFile"))
            .map(n => ({
              url: (n.textContent||"").trim(),
              type: n.getAttribute("type") || "",
              bitrate: parseInt(n.getAttribute("bitrate")||"0",10),
              width: parseInt(n.getAttribute("width")||"0",10),
              delivery: (n.getAttribute("delivery")||"").toLowerCase()
            }))
            .filter(m => m.url && /mp4/i.test(m.type||""));
          mediaNodes.sort((a,b)=> (a.bitrate||a.width||0) - (b.bitrate||b.width||0));
          const mediaFile = mediaNodes[0]?.url || xml.querySelector("MediaFile")?.textContent.trim();

          const clickThroughURL = xml.querySelector("ClickThrough")?.textContent.trim() || '';
          const impressionUrls = Array.from(xml.querySelectorAll("Impression")).map(n=>n.textContent.trim());
          const clickTrackingUrls = Array.from(xml.querySelectorAll("ClickTracking")).map(n=>n.textContent.trim());
          const trackingEvents = { start:[], firstQuartile:[], midpoint:[], thirdQuartile:[], complete:[], skip:[], error:[] };
          xml.querySelectorAll("Tracking").forEach(n=>{
            const ev = n.getAttribute("event");
            if (trackingEvents[ev]) trackingEvents[ev].push(n.textContent.trim());
          });
          const errorUrls = Array.from(xml.querySelectorAll("Error")).map(n=>n.textContent.trim());
          trackingEvents.error.push(...errorUrls);

          let skipOffsetSec = null;
          const skipOffset = xml.querySelector("Linear")?.getAttribute("skipoffset");
          if (skipOffset) {
            const [h,m,s] = skipOffset.split(':').map(Number);
            skipOffsetSec = (h||0)*3600 + (m||0)*60 + (s||0);
          }

          this.prefetched = { mediaFile, clickThroughURL, impressionUrls, clickTrackingUrls, trackingEvents, skipOffsetSec };
          return this.prefetched;
        } finally { clearTimeout(t); }
      }

      async launchVASTThenPopups() {
        await this.launchVASTWithWatchdogs();
        this.startPopupsFlow();
      }

      fireTracking(urls, label) {
        (urls||[]).forEach(url => { const img = new Image(); img.src = url; });
        if (label) console.debug('[VAST]', label);
      }

      launchVASTWithWatchdogs() {
        return new Promise(async (resolve) => {
          const container = this.container;
          if (!container) return resolve('no-container');

          const vast = document.createElement('div');
          vast.className = 'vast-container vast-hidden';
          vast.innerHTML = `
            <video id="vast-video" class="video-js vjs-default-skin" autoplay muted playsinline webkit-playsinline></video>
            <div class="vast-spinner" id="vast-spinner"></div>
            <div id="vast-timer" class="vast-timer">Advertising • 0:00</div>
            <button id="vast-skip-btn" class="vast-skip-btn">
              <span>SKIP ADS</span>
              <div id="svg-skip" class="svg-hidden">
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8.5 9.25V0.75C8.5 0.5375 8.57146 0.359375 8.71437 0.215624C8.85729 0.0718745 9.03437 0 9.24562 0C9.45687 0 9.63542 0.0718745 9.78125 0.215624C9.92708 0.359375 10 0.5375 10 0.75V9.25C10 9.4625 9.92854 9.64063 9.78563 9.78438C9.64271 9.92813 9.46563 10 9.25438 10C9.04313 10 8.86458 9.92813 8.71875 9.78438C8.57292 9.64063 8.5 9.4625 8.5 9.25ZM0 8.11896V1.88188C0 1.65507 0.0759026 1.47222 0.227708 1.33333C0.379653 1.19444 0.556806 1.125 0.759167 1.125C0.8225 1.125 0.885417 1.13542 0.947917 1.15625C1.01042 1.17708 1.07639 1.20833 1.14583 1.25L6.02083 4.375C6.13194 4.45056 6.21528 4.54188 6.27083 4.64896C6.32639 4.75604 6.35417 4.87257 6.35417 4.99854C6.35417 5.12451 6.32639 5.24139 6.27083 5.34917C6.21528 5.45694 6.13194 5.54889 6.02083 5.625L1.14583 8.75C1.07639 8.79167 1.01042 8.82292 0.947917 8.84375C0.885417 8.86458 0.8225 8.875 0.759167 8.875C0.556806 8.875 0.379653 8.80569 0.227708 8.66708C0.0759026 8.52847 0 8.34576 0 8.11896Z" fill="white"/>
              </div>
            </button>
            <div id="vast-progress-bar" class="vast-progress-bar"></div>
            <div id="vast-click-overlay" class="vast-click-overlay" style="display:none;"></div>
          `;
          container.appendChild(vast);

          const player = videojs('vast-video', {
            controls:false, autoplay:true, muted:true, preload:'auto',
            userActions:{ doubleClick:false }, nativeControlsForTouch:false
          });
          this.vjsPlayer = player; // <- on garde une référence

          const spinner = vast.querySelector('#vast-spinner');
          const skipBtn = vast.querySelector('#vast-skip-btn');
          const clickOverlay = vast.querySelector('#vast-click-overlay');
          const timer = vast.querySelector('#vast-timer');
          const progressBar = vast.querySelector('#vast-progress-bar');
          const showCountdownUI = () => { timer.style.display='block'; progressBar.style.display='block'; };

          let data = this.prefetched;
          if (!data) { try { data = await this.prefetchVAST(this.vastUrl, 10000); } catch(e){} }
          if (!data || !data.mediaFile) { vast.remove(); return resolve('prefetch-fail'); }

          let { mediaFile, clickThroughURL, impressionUrls, clickTrackingUrls, trackingEvents, skipOffsetSec } = data;
          player.src({ src: mediaFile, type:'video/mp4' });

          let started = false, freezeTimer = null, resolved = false;
          const done = (reason) => {
            if (resolved) return; resolved = true;
            try { clearTimeout(startupTimer); } catch(e){}
            try { if (freezeTimer) clearInterval(freezeTimer); } catch(e){}
            try { player.dispose(); } catch(e){}
            vast.remove();
            resolve(reason);
          };

          const startupTimer = setTimeout(() => {
            if (!started) { this.fireTracking(trackingEvents?.error, 'startup-timeout'); done('startup-timeout'); }
          }, 10000);

          const startFreezeWatch = () => {
            let lastT = 0, lastTick = performance.now();
            freezeTimer = setInterval(() => {
              const now = performance.now();
              const ct = player.currentTime();
              if (!player.paused() && ct === lastT && (now - lastTick) > 10000) {
                this.fireTracking(trackingEvents?.error, 'freeze-timeout');
                done('freeze-timeout');
              }
              if (ct !== lastT){ lastT = ct; lastTick = now; }
            }, 500);
          };

          // --- 🔊 clé: si l'utilisateur a déjà interagi AVANT que le player n'existe,
          // on démutera dès que possible (premier play/timeupdate).
          const tryDeferredUnmute = () => {
            if (this._wantUnmute && player) this.__attemptUnmuteNow(player);
          };

          player.on('timeupdate', () => {
            vast.classList.remove('vast-hidden');
            const ct = player.currentTime();
            if (!started && ct > 0) {
              started = true;
              clearTimeout(startupTimer);
              spinner.style.display = 'none';
              skipBtn.style.display = 'flex';

              // UI secondaires
              if (skipOffsetSec != null) { showCountdownUI(); skipBtn.disabled = true; skipBtn.classList.add('is-disabled'); }
              clickOverlay.style.display = clickThroughURL ? 'block' : 'none';

              this.fireTracking(impressionUrls, 'impression');
              this.fireTracking(trackingEvents?.start, 'start');

              this.startProgressLoop(player, timer, progressBar, trackingEvents, skipBtn, skipOffsetSec);
              startFreezeWatch();

              // si geste déjà fait, on unmute maintenant
              tryDeferredUnmute();
            }
          });

          // bonus: si l'onglet revient en focus et qu'on veut du son, on retente
          document.addEventListener('visibilitychange', () => {
            if (!document.hidden) tryDeferredUnmute();
          });

          player.on('waiting', () => { spinner.style.display='grid'; });
          player.on('stalled', () => { spinner.style.display='grid'; });
          player.on('playing', () => { spinner.style.display='none'; });

          player.on('ended', () => { this.fireTracking(trackingEvents?.complete, 'complete'); done('complete'); });
          skipBtn.addEventListener('click', () => {
            if (skipBtn.disabled) return;
            this.fireTracking(trackingEvents?.skip, 'skip');
            done('skip');
          });
          clickOverlay.addEventListener('click', () => {
            if (clickThroughURL) {
              this.fireTracking(clickTrackingUrls, 'click');
              window.open(clickThroughURL, '_blank');
            }
          });
        });
      }

      startProgressLoop(player, timer, progressBar, trackingEvents, skipBtn, skipOffsetSec) {
        let rafId = null;
        let q = { q1:false, q2:false, q3:false };
        const loop = () => {
          const ct = player.currentTime();
          const dur = player.duration() || 0.001;

          if (skipOffsetSec != null) {
            const remaining = Math.max(0, Math.ceil(skipOffsetSec - ct));
            if (remaining > 0) {
              if (!skipBtn.disabled) { skipBtn.disabled = true; skipBtn.classList.add('is-disabled'); }
              skipBtn.querySelector('span').textContent = `Skip in ${remaining}`;
              timer.textContent = `Advertising • 0:${String(remaining).padStart(2,'0')}`;
              progressBar.style.width = `${Math.min((ct/skipOffsetSec)*100,100)}%`;
            } else {
              if (skipBtn.disabled) { skipBtn.disabled = false; skipBtn.classList.remove('is-disabled'); }
              skipBtn.querySelector('span').textContent = 'SKIP ADS';
              timer.textContent = `Advertising • 0:00`;
              progressBar.style.width = '100%';
            }
          }

          if (!q.q1 && ct >= dur*0.25){ q.q1=true; this.fireTracking(trackingEvents?.firstQuartile,'firstQuartile'); }
          if (!q.q2 && ct >= dur*0.50){ q.q2=true; this.fireTracking(trackingEvents?.midpoint,'midpoint'); }
          if (!q.q3 && ct >= dur*0.75){ q.q3=true; this.fireTracking(trackingEvents?.thirdQuartile,'thirdQuartile'); }

          rafId = requestAnimationFrame(loop);
        };
        rafId = requestAnimationFrame(loop);
      }

      // ----- Popups après la VAST -----
      startPopupsFlow() {
        const container = this.container;
        if (!container || this.maxPopups <= 0 || this.popupUrls.length === 0) return;
        let popupCount = 0;

        const createOverlay = () => {
          const overlay = document.createElement('div');
          Object.assign(overlay.style, {
            position:'absolute', top:0, left:0, width:'100%', height:'100%',
            zIndex:50, opacity:0
          });
          overlay.addEventListener('click', (e) => {
            e.preventDefault();
            if (popupCount < this.maxPopups) {
              const url = this.popupUrls[popupCount % this.popupUrls.length];
              window.open(url, '_blank');
              popupCount++;
              container.removeChild(overlay);
              if (popupCount < this.maxPopups) {
                setTimeout(createOverlay, this.delayBetweenPopups);
              }
            }
          });
          container.appendChild(overlay);
        };

        setTimeout(createOverlay, this.popupInitialDelay);
        setTimeout(createOverlay, this.delayBetweenPopups || 0);
      }
    }
    customElements.define('player-ad-overlay', PlayerAdOverlay);
    </script>

</player-ad-overlay>
