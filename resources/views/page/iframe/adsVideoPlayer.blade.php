<html>
  <head>
    <meta name="robots" content="noindex">
    <title>{{ $title }}</title>
    <style>
      body, html {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
      }

      .player-container {
        position: relative;
        aspect-ratio: 16/9 !important;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #000;
        overflow: hidden;
      }

      .active-video-player {
        aspect-ratio: 16/9;
        height: 100%;
        z-index: 1;
      }

      .background-video-player {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: blur(30px);
        opacity: 0.5;
      }
      .ads-overlay{
        position:absolute; inset:0;
        z-index:10;
        display:flex; align-items:center; justify-content:center;
        background:rgba(0,0,0,0);
        border:0; margin:0; padding:0;
        width:100%; height:100%;
        cursor: pointer;
      }
      .ads-overlay:focus-visible{ outline:2px solid #fff; outline-offset:2px; }
      .overlay-counter {
          display: flex;
          padding: 12px 16px;
          border-radius: 999px;
          background: rgb(43 43 43 / 62%);
          font-weight: 600;
          font-size: 14px;
          backdrop-filter: blur(10px);
          -webkit-backdrop-filter: blur(10px);
      }

      .overlay-content {
          transform: translateY(34px);
          color: #fff;
          text-align: center;
          font-family: '{{ theme('font_family') }}', sans-serif;
      }
      .overlay-title{ font-weight:700; font-size:20px; margin-bottom:8px; }
      .overlay-text{ font-size:14px; opacity:.95; margin-bottom:10px; }
    </style>
  </head>
  <body>
    @php
      $useBabastream = !$playerBunnyUrl && !$playerVoeUrl && $playerBabastreamUrl;
    @endphp
    <div class="player-container p0pups-container" style="position: relative;">
      @if($playerBunnyUrl)
      <iframe class="active-video-player" src="{{ $playerBunnyUrl }}?autoplay=false&loop=false&muted=false&preload=false&responsive=true" title="{{ $title }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
      @elseif($playerVoeUrl)
      <iframe class="active-video-player" src="{{ $playerVoeUrl }}" title="{{ $title }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
      @elseif($playerBabastreamUrl)
      <iframe class="active-video-player" src="{{ $playerBabastreamUrl }}" title="{{ $title }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
      @endif
    </div>

    @if(!$isPremium && !$useBabastream)
      <script>
      (function () {
        // ===== Config =====
        @if($popupConfig && !empty($popupConfig['items']))
          @php
            $items = $popupConfig['items'];
            $urls = array_map(fn($item) => $item['href'], $items);
          @endphp
          const CONFIG = {
            DIRECT_LINK_POPUPS: {{ count($items) }},
            EXTERNAL_POPUP_SCRIPTS_COUNT: {{ $popupConfig['settings']['external_popup_scripts_count'] ?? 0 }},
            RANDOM: {{ ($popupConfig['settings']['random'] ?? false) ? 'true' : 'false' }},
            POPUP_URLS: @json($urls),
            ALLOWED_PARENT: @json(config('app.url'))
          };
        @else
          const CONFIG = {
            DIRECT_LINK_POPUPS: 0,
            EXTERNAL_POPUP_SCRIPTS_COUNT: 0,
            RANDOM: false,
            POPUP_URLS: [],
            ALLOWED_PARENT: @json(config('app.url'))
          };
        @endif

        // ===== State =====
        const STATE = {
          ourClicks: 0,
          firstClickPending: true,
          remainingPopups: 0,
          overlayPosition: 1
        };

        // ===== Helpers =====
        const clampInt = (n, min=0) => Math.max(min, parseInt(n,10)||0);
        const shuffleInPlace = (arr) => { for (let i=arr.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [arr[i],arr[j]]=[arr[j],arr[i]];} return arr; };
        const firstClickLot = (cfg) => 1 + clampInt(cfg.EXTERNAL_POPUP_SCRIPTS_COUNT, 0);
        const totalPopups = (cfg) => clampInt(cfg.DIRECT_LINK_POPUPS,0) + clampInt(cfg.EXTERNAL_POPUP_SCRIPTS_COUNT,0);
        const totalClicksNeeded = (cfg) => {
          const tot = totalPopups(cfg), lot = firstClickLot(cfg);
          if (tot === 0) return 0;
          return (tot <= lot) ? 1 : 1 + (tot - lot);
        };
        const clicksLeftDynamic = (remainingPopups, isFirst, lot) => {
          const rem = clampInt(remainingPopups, 0);
          if (rem === 0) return 0;
          if (isFirst) return (rem <= lot) ? 1 : 1 + (rem - lot);
          return rem;
        };

        @php
          $adsTranslations = [
            'aria' => t__('ads.unlock_video'),
            'click_singular' => t__('ads.click_singular'),
            'clicks_plural' => t__('ads.clicks_plural'),
            'remaining_singular' => t__('ads.remaining_singular'),
            'remaining_plural' => t__('ads.remaining_plural'),
          ];
        @endphp

        const TRANSLATIONS = @json($adsTranslations);
        const s = (n, one, many) => (n===1?one:many);

        const t = {
          aria: TRANSLATIONS.aria,
          counter: n => `${n} ${s(n, TRANSLATIONS.click_singular, TRANSLATIONS.clicks_plural)} ${s(n, TRANSLATIONS.remaining_singular, TRANSLATIONS.remaining_plural)}`
        };

        // ===== UI =====
        function buildOverlay(t, initialLeft){
          const btn=document.createElement('button');
          btn.id='overlay'; btn.type='button'; btn.className='ads-overlay'; btn.setAttribute('aria-label', t.aria);
          btn.innerHTML=`
            <div class="overlay-content" aria-live="polite">
              <div class="overlay-counter" id="clicksLeft">${t.counter(initialLeft)}</div>
            </div>`;
          return btn;
        }
        function updateOverlayTexts(overlayEl, t, clicksLeftNow){
          overlayEl.querySelector('#clicksLeft').textContent = t.counter(clicksLeftNow);
        }

        // ===== Actions =====
        function openOurPopup(urls, clickIndex){
          if (STATE.ourClicks >= clampInt(CONFIG.DIRECT_LINK_POPUPS,0)) return;
          const url = urls[clickIndex % urls.length];
          window.open(url, '_blank');
        }
        function handleClick(e, overlayEl, t){
          e.preventDefault();

          openOurPopup(CONFIG.POPUP_URLS, STATE.ourClicks);
          STATE.ourClicks++;

          const lot = firstClickLot(CONFIG);
          const decrement = STATE.firstClickPending ? lot : 1;
          STATE.remainingPopups = Math.max(0, STATE.remainingPopups - decrement);
          STATE.firstClickPending = false;

          window.parent.postMessage(
            { gtmEvent: `popup_position_${STATE.overlayPosition}` },
            CONFIG.ALLOWED_PARENT
          );

          STATE.overlayPosition++;

          if (STATE.remainingPopups === 0) { overlayEl.remove(); return; }

          const left = clicksLeftDynamic(STATE.remainingPopups, STATE.firstClickPending, lot);
          updateOverlayTexts(overlayEl, t, left);
        }

        // ===== Init =====
        function init(){
          const container=document.querySelector('.p0pups-container');
          if(!container) return;

          if (CONFIG.DIRECT_LINK_POPUPS === 0 && CONFIG.EXTERNAL_POPUP_SCRIPTS_COUNT === 0) return;

          if (CONFIG.RANDOM) shuffleInPlace(CONFIG.POPUP_URLS);

          STATE.remainingPopups = totalPopups(CONFIG);
          const lot = firstClickLot(CONFIG);
          const initialLeft = clicksLeftDynamic(STATE.remainingPopups, STATE.firstClickPending, lot);

          const overlay = buildOverlay(t, initialLeft);
          overlay.addEventListener('click', (e)=>handleClick(e, overlay, t));
          container.appendChild(overlay);
        }

        setTimeout(init, 1);
      })();
      </script>
    @endif
  </body>
</html>
