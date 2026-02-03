<?php

use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\Cache;

if (! function_exists('theme')) {
    /**
     * Get theme setting value
     *
     * @param  string  $key  Theme key (e.g., 'site_name', 'primary_color')
     * @param  mixed  $default  Default value if not set
     */
    function theme(string $key, mixed $default = null): mixed
    {
        return config("tentacle.theme.{$key}", $default);
    }
}

if (! function_exists('popup_config')) {
    /**
     * Get popup configuration (settings + items)
     */
    function popup_config(): array
    {
        return config('tentacle.popup', [
            'settings' => ['external_popup_scripts_count' => 0, 'random' => false],
            'items' => [],
        ]);
    }
}

if (! function_exists('preroll_items')) {
    /**
     * Get active preroll items
     */
    function preroll_items(): array
    {
        return config('tentacle.preroll.items', []);
    }
}

if (! function_exists('t__')) {
    /**
     * Translate the given message from database
     *
     * @param  string  $key  Translation key (e.g., 'navigation.home')
     * @param  array  $replace  Replacements for placeholders like :name, :count
     * @param  string|null  $locale  Locale override
     */
    function t__(string $key, array $replace = [], ?string $locale = null): string
    {
        // Return the key if app is not booted yet (during composer autoload)
        if (! app()->isBooted()) {
            return $key;
        }

        try {
            $locale = $locale ?? config('app.locale', 'en');

            return TranslationHelper::get($key, $locale, $replace);
        } catch (\Exception $e) {
            return $key;
        }
    }
}

if (! function_exists('route_trans')) {
    /**
     * Get translated route segment
     *
     * @param  string  $key  Route key (e.g., 'videos', 'categories')
     * @param  string|null  $locale  Locale override
     */
    function route_trans(string $key, ?string $locale = null): string
    {
        if ($locale === null) {
            $locale = config('app.locale', 'en');
        }

        try {
            $routes = Cache::remember('translated_routes', 3600, function () {
                return \App\Models\TranslatedRoute::all()->pluck('translations', 'key')->toArray();
            });

            return $routes[$key][$locale] ?? $key;
        } catch (\Exception $e) {
            return $key;
        }
    }
}

if (! function_exists('seo_meta')) {
    /**
     * Get SEO meta value from textseo object with optional placeholder replacement
     *
     * @param  object  $textseo  The textseo object from view
     * @param  string  $page  The page name (home, videos, video, category, model, tag, search, etc.)
     * @param  string  $field  The field name (meta_title, meta_description, image_url, h1, h2, breadcrumb)
     * @param  array  $replacements  Associative array of placeholders to replace ['name' => 'value', 'description' => 'value']
     * @param  string  $default  Default fallback value
     */
    function seo_meta(object $textseo, string $page, string $field, array $replacements = [], string $default = ''): string
    {
        try {
            $value = $textseo->{$page}->{$field} ?? $default;

            foreach ($replacements as $key => $replacement) {
                $value = str_replace("<{$key}>", $replacement ?? '', $value);
            }

            return $value;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
