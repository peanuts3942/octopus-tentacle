{{-- JavaScript Translations Injection --}}
@php
$jsTranslations = [
    // Common
    'common.search' => t__('common.search'),
    'common.close' => t__('common.close'),
    'common.more' => t__('common.more'),
    'common.days' => t__('common.days'),
    'common.url_copied' => t__('common.url_copied'),
    'common.view_more' => t__('common.view_more'),
    'common.view_less' => t__('common.view_less'),
    'common.view' => t__('common.view'),
    'common.views' => t__('common.views'),
    'common.liked' => t__('common.liked'),
    'common.reported' => t__('common.reported'),
    'common.broken' => t__('common.broken'),
    'common.show_similar_videos' => t__('common.show_similar_videos'),
    'common.show_same_models' => t__('common.show_same_models'),
    'common.show_same_videos' => t__('common.show_same_videos'),
    'common.show_more_videos' => t__('common.show_more_videos'),
    'common.show_more_models' => t__('common.show_more_models'),
    'common.no_result_no' => t__('common.no_result_no'),
    'common.no_result_found' => t__('common.no_result_found'),
    'common.same_videos' => t__('common.same_videos'),
    'common.show' => t__('common.show'),

    // Tab filters
    'tab_filters.videos' => t__('tab_filters.videos'),
    'tab_filters.models' => t__('tab_filters.models'),
    'tab_filters.feats' => t__('tab_filters.feats'),
    'tab_filters.more_videos' => t__('tab_filters.more_videos'),

    // TIME AGO
    'time.today' => t__('time.today'),
    'time.yesterday' => t__('time.yesterday'),
    'time.day_ago' => t__('time.day_ago'),
    'time.days_ago' => t__('time.days_ago'),
    'time.week_ago' => t__('time.week_ago'),
    'time.weeks_ago' => t__('time.weeks_ago'),
    'time.month_ago' => t__('time.month_ago'),
    'time.months_ago' => t__('time.months_ago'),
    'time.year_ago' => t__('time.year_ago'),
    'time.years_ago' => t__('time.years_ago'),
    'time.new' => t__('time.new'),

    // NAVIGATION
    'navigation.home' => t__('navigation.home'),
    'navigation.models' => t__('navigation.models'),
    'navigation.videos' => t__('navigation.videos'),
    'navigation.categories' => t__('navigation.categories'),
    'navigation.search' => t__('navigation.search'),

    // ADS
    'ads.contact_us_now' => t__('ads.contact_us_now'),
    'ads.promote_your_business' => t__('ads.promote_your_business'),
    'ads.ad' => t__('ads.ad'),
];

$jsRoutes = [
    'home' => '/',
    'search' => '/' . route_trans('search'),
    'videos' => '/' . route_trans('videos'),
    'models' => '/' . route_trans('models'),
    'categories' => '/' . route_trans('categories'),
    'tags' => '/' . route_trans('tags'),
];
@endphp

<script>
    window.translations = @json($jsTranslations);
    window.routes = @json($jsRoutes);

    /**
     * JavaScript translation helper function
     * Usage: t('common.search')
     */
    window.t = function(key) {
        return window.translations[key] || key;
    };
</script>
