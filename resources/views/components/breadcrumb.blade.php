<nav class="breadcrumb" aria-label="Breadcrumb">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        @foreach($items as $index => $item)
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                @if(!$loop->last)
                    <a itemprop="item" href="{{ $item['url'] }}">
                        <span itemprop="name">{{ $item['name'] }}</span>
                    </a>
                    @include('icons.arrowRight')
                @else
                    <span class="last-breadcrumb-item" itemprop="name">{{ $item['name'] }}</span>
                @endif
                <meta itemprop="position" content="{{ $index + 1 }}" />
            </li>
        @endforeach
    </ol>
</nav>