<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StaticText extends Model
{
    protected $fillable = ['key', 'group', 'translations', 'description'];

    protected $casts = [
        'translations' => 'array',
    ];

    /**
     * Get translation for specific locale
     */
    public function translate(string $locale): ?string
    {
        return $this->translations[$locale] ?? null;
    }

    /**
     * Update translation for specific locale
     */
    public function setTranslation(string $locale, string $value): void
    {
        $translations = $this->translations;
        $translations[$locale] = $value;
        $this->translations = $translations;
        $this->save();

        Cache::forget("static_texts.{$locale}");
    }

    /**
     * Boot method to clear cache on update
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            $locales = config('app.supported_locales', ['en', 'fr', 'de', 'es', 'it', 'pt']);
            foreach ($locales as $locale) {
                Cache::forget("static_texts.{$locale}");
            }
        });

        static::deleted(function () {
            $locales = config('app.supported_locales', ['en', 'fr', 'de', 'es', 'it', 'pt']);
            foreach ($locales as $locale) {
                Cache::forget("static_texts.{$locale}");
            }
        });
    }
}
