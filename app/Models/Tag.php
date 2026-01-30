<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Tag extends Model
{
    use Searchable;

    protected $fillable = [
        'exclude_from_translation',
    ];

    protected $appends = ['name', 'slug'];

    // Relations
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class);
    }

    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TagTranslation::class);
    }

    public function translation(): HasOne
    {
        $locale = app()->getLocale();

        return $this->hasOne(TagTranslation::class)
            ->where('locale', $locale)
            ->withDefault(fn () => $this->translations()->first());
    }

    // Accessors for translated fields
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation?->name ?? 'Unnamed'
        );
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation?->slug ?? $this->id
        );
    }

    // MeiliSearch
    public function toSearchableArray(): array
    {
        $this->loadMissing('translation');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }

    protected function makeAllSearchableUsing($query)
    {
        return $query->with('translation');
    }
}
