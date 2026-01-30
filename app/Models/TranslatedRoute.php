<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TranslatedRoute extends Model
{
    protected $fillable = ['key', 'translations'];

    protected $casts = [
        'translations' => 'array',
    ];

    /**
     * Get translation for specific locale
     */
    public function translate(string $locale): string
    {
        return $this->translations[$locale] ?? $this->key;
    }

    /**
     * Boot method to clear cache on update
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('translated_routes');
        });

        static::deleted(function () {
            Cache::forget('translated_routes');
        });
    }
}
