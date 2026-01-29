<div class="player-container-wrapper">
    <iframe 
        src="{{ $url }}" 
        frameborder="0"
        class="active-video-player"
        title="{{ $title }}"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        referrerpolicy="strict-origin-when-cross-origin"
        allowfullscreen
        loading="lazy"
        width="100%"
        height="100%"
        style="aspect-ratio: 16/9;"
    ></iframe>
</div>