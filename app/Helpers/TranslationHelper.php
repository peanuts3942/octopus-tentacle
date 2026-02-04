<?php

namespace App\Helpers;

use App\Models\StaticText;
use App\Models\TextSeo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationHelper
{
    /**
     * Get all static text translations for current locale from cache
     */
    public static function getAll(string $locale): array
    {
        return Cache::remember("static_texts.{$locale}", 86400, function () use ($locale) {
            $texts = StaticText::all();
            $translations = [];

            foreach ($texts as $text) {
                $translations[$text->key] = $text->translate($locale) ?? $text->translate('en') ?? $text->key;
            }

            return $translations;
        });
    }

    /**
     * Get single translation with placeholder replacement
     */
    public static function get(string $key, string $locale, array $replace = []): string
    {
        $translations = self::getAll($locale);
        $value = $translations[$key] ?? $key;

        // Handle replacements like :name, :count, etc.
        foreach ($replace as $search => $replacement) {
            $value = str_replace(":{$search}", $replacement, $value);
        }

        // Log missing translations in development
        if (! isset($translations[$key]) && config('app.debug')) {
            Log::warning("Missing translation key: {$key} for locale: {$locale}");
        }

        return $value;
    }

    /**
     * Get all SEO texts for current locale from cache
     * Returns formatted object with page-specific SEO data
     * Auto-generates cache if not found
     * Replaces "BornToBeFuck" with tentacle's site_name
     */
    public static function getSeoTexts(string $locale, ?int $tentacleId = null): object
    {
        $cacheKey = $tentacleId
            ? "textseo_formatted_{$locale}_{$tentacleId}"
            : "textseo_formatted_{$locale}";

        return Cache::remember($cacheKey, 86400, function () use ($locale) {
            try {
                $siteName = config('tentacle.settings.site_name', config('app.name'));

                $textSeos = TextSeo::with(['translations' => function ($query) use ($locale) {
                    $query->where('locale', $locale);
                }])->get();

                $formattedData = new \stdClass;

                foreach ($textSeos as $textSeo) {
                    $translation = $textSeo->translations->first();

                    if ($translation) {
                        $pageData = new \stdClass;
                        $pageData->image_url = $translation->image_url;
                        $pageData->meta_title = self::replaceSiteName($translation->meta_title, $siteName);
                        $pageData->meta_description = self::replaceSiteName($translation->meta_description, $siteName);
                        $pageData->h1 = self::replaceSiteName($translation->h1, $siteName);
                        $pageData->h2 = self::replaceSiteName($translation->h2, $siteName);
                        $pageData->breadcrumb = self::replaceSiteName($translation->breadcrumb, $siteName);

                        $formattedData->{$textSeo->name} = $pageData;
                    }
                }

                return $formattedData;
            } catch (\Exception $e) {
                Log::error("Failed to load TextSeo for locale {$locale}: ".$e->getMessage());

                return new \stdClass;
            }
        });
    }

    /**
     * Replace "BornToBeFuck" and "BTBF" with the tentacle's site name
     */
    private static function replaceSiteName(?string $text, string $siteName): ?string
    {
        if ($text === null) {
            return null;
        }

        return str_ireplace(['BornToBeFuck', 'BTBF'], $siteName, $text);
    }
}
