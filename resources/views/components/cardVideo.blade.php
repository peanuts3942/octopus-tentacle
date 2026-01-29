<article class="cardVideo">
    <a  href="{{ route('video.show', ['id' => $video->id, 'slug' => $video->slug]) }}"
        class="cardVideo-image-container trans"
    >
        <div class="preloadLine" style="opacity: 0; width: 0px;"></div>
        <img
            src="{{ $video->thumbnail_url }}"
            alt="{{ $video->title }}"
            class="cardVideo-image"
            data-preview-url="{{ $video->preview_url ?? '' }}"
        >
        @if($video->time)
            <div id="video-duration" class="cardVideo-duration" data-duration="{{ $video->time }}"></div>
        @endif
    </a>
    <div class="cardVideo-infos">
        <h3 class="cardVideo-title trans">
            <a href="{{ route('video.show', ['id' => $video->id, 'slug' => $video->slug]) }}">
                {{ $video->title }}
            </a>
        </h3>
        <div class="cardVideo-date-channel-container">
            <p class="cardVideo-date" data-published="{{ $video->published_at?->timestamp ?? now()->timestamp }}"></p>
            <span class="cardVideo-separator">•</span>
            @if($video->channel)
            <h4 class="cardVideo-channel trans">
                <a href="{{ route('model.show', ['slug' => $video->channel->slug]) }}">{{ $video->channel->name }}</a>
            </h4>
            @endif
        </div>
    </div>
</article>
