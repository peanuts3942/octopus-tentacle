@php
    $section = $section ?? 'default';
    $isSidebar = $section === 'sidebar';
    $containerClass = $isSidebar ? 'ad-carousel ad-carousel-sidebar' : 'ad-carousel';
    $linkClass = $isSidebar ? 'ad_carousel_sidebar' : 'ad_carousel_homepage';
    $imgClass = ($isSidebar || $section === 'footer') ? 'slide ad-img' : 'slide';
@endphp

<ad-carousel 
    data-random='@json($random)'
    data-show-only-one-randomly='@json($show_only_one_randomly)'
    data-container-class="{{ $containerClass }}"
    data-link-class="{{ $linkClass }}">
    <div class="{{ $containerClass }}">
        @foreach ($sliders as $index => $slider)
            <a href="{{ $slider['href'] }}" 
               class="{{ $linkClass }} {{ $index === 0 ? 'active' : '' }}"
               style="{{ $index !== 0 ? 'transform: translateX(100%);' : '' }}"
               rel="nofollow noopener" 
               target="_blank">
                <img src="{{ $slider['image_path'] }}" 
                     alt="{{ $slider['alt'] ?? 'ad-mym' }}" 
                     class="{{ $imgClass }}" 
                     style="width: 100%">
            </a>
        @endforeach
    </div>
</ad-carousel>