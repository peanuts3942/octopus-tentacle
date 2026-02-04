<nav aria-label="Navigation tabbar mobile"  itemscope itemtype="http://schema.org/SiteNavigationElement">

    <ul class="tabbar">
        <li>
            <a href="{{ route('category.index') }}" id="tabbar-nav_page-categories-mobile" class="tabbar-btn categories_bar" itemprop="url">
                @include('icons.categories')
                <span class="tabbar-txt">Catégories</span>
            </a>
        </li>
        <li>
            <button id="tabbar-nav_page-search-mobile" class="tabbar-btn open-search-modal search_bar" open-search-modal>
                @include('icons.search')
                <span itemprop="name" class="tabbar-txt">Recherche</span>
            </button>
        </li>
        <li>
            <a href="{{ route('home') }}" id="nav_page-trending-mobile" class="tabbar-btn home_bar" itemprop="url">
                @include('icons.screenVideos')
                <span itemprop="name" class="tabbar-txt">Vidéos</span>
            </a>
        </li>
        <li>
            <a href="{{ route('model.index') }}" id="nav_page-models-mobile" class="tabbar-btn models_bar" itemprop="url">
                @include('icons.models')
                <span itemprop="name" class="tabbar-txt">Models</span>
            </a>
        </li>
    </ul>

</nav>