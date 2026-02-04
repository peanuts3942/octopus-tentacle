<sticky-header class="header-mobile">

    <div class="nav-content">
        <a class="btbf_logo_header" href="/">
            <span class="text-lg font-bold text-white">{{ config('app.name') }}</span>
        </a>

        <div class="header-right">
            <!-- <a href="" rel="nofollow noopener" target="_blank" class="header-telegram socials_link_bar">
                <span class="header-telegram-txt">Telegram</span>
                <img src="{{ asset('images/banner/telegram.svg') }}" alt="telegram-icon" class="icon">
            </a> -->

            <button class="search-btn open-search-modal search_header" open-search-modal>
                @include('icons.search')
            </button>

            <button class="megamenu-btn burger_menu" aria-label="Menu Mobile" aria-expanded="false" aria-controls="menu">
                @include('icons.burger')
            </button>

        </div>
    </div>

</sticky-header>




<x-search-modal class="search-modal-mobile"/>

<div class="menu" role="navigation">

    <div class="megamenu-content">

        <nav aria-label="Navigation menu mobile" itemscope itemtype="http://schema.org/SiteNavigationElement">
            <ul class="menu-nav">
                <li class="megamenu-nav-item nav-item-with-submenu">
                    <a href="{{ route('home') }}" class="menu-btn videos_menu" id="megamenu-nav_page-videos-mobile">
                        @include('icons.screenVideos')
                        <span class="menu-txt">Vidéos</span>
                    </a>
                </li>
                <li class="megamenu-nav-item nav-item-with-submenu">
                    <a href="{{ route('category.index') }}" class="menu-btn categories_menu" id="megamenu-nav_page-categories-mobile">
                        @include('icons.categories')
                        <span class="menu-txt">Catégories</span>
                    </a>
                </li>
                <li class="megamenu-nav-item nav-item-with-submenu">
                    <a href="{{ route('model.index') }}" class="menu-btn models_menu" id="megamenu-nav_page-models-mobile">
                        @include('icons.models')
                        <span class="menu-txt">Modèles</span>
                    </a>
                </li>
            </ul>
        </nav>

    </div>

</div>