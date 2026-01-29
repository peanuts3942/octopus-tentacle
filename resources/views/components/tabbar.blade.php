<nav aria-label="Navigation tabbar mobile"  itemscope itemtype="http://schema.org/SiteNavigationElement">

    <ul class="tabbar">
        <li>
            <a href="/categories" id="tabbar-nav_page-categories-mobile" class="tabbar-btn categories_bar" itemprop="url">
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
            <a href="/" id="nav_page-trending-mobile" class="tabbar-btn home_bar" itemprop="url">
                @include('icons.screenVideos')
                <span itemprop="name" class="tabbar-txt">Vidéos</span>
            </a>
        </li>
        <li>
            <a href="https://borntobefuck.cam"
            target="_blank" rel="nofollow noopener" id="first-ad_link-mobile" class="tabbar-btn ad_link_bar" itemprop="url">
                @include('icons.camera')
                <span class="tabbar-txt">Live Cam</span>
            </a>
        </li>
        <li>
            <a href="https://t.me/m/3ps2iHw5ZjBk"
            target="_blank" rel="nofollow noopener" id="second-ad_link-mobile" class="tabbar-btn ad_link_bar" itemprop="url">
                @include('icons.girl')
                <span class="tabbar-txt">Plan cul</span>
            </a>
        </li>
    </ul>

</nav>