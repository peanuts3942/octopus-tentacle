<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Channel extends Model
{
    use Searchable;

    protected $fillable = [
        'name',
        'slug',
        'card_picture_url',
        'profile_picture_url',
        'nationality_iso',
        'nationality',
        'onlyfans_url',
        'mym_url',
        'patreon_url',
        'platform_click',
        'mym_id',
    ];

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ChannelAlias::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ChannelTranslation::class);
    }

    public function translation(): HasOne
    {
        $locale = app()->getLocale();

        return $this->hasOne(ChannelTranslation::class)
            ->where('locale', $locale)
            ->withDefault(fn () => $this->translations()->first());
    }

    public function scopeIndexable($query)
    {
        $zone = config('app.zone');
        $nationalities = config("app.zone_{$zone}_nationalities", []);
        $tentacleId = config('app.tentacle_id');

        return $query
            ->whereIn('nationality_iso', $nationalities)
            ->whereHas('videos', fn ($q) => $q
                ->where('is_published', true)
                ->whereJsonContains('available_zones', $zone)
                ->where(function ($q2) use ($tentacleId) {
                    // No override → check videos.draft
                    $q2->where(function ($inner) use ($tentacleId) {
                        $inner->whereDoesntHave('tentacleVideos', fn ($tv) => $tv->where('tentacle_id', $tentacleId))
                            ->where('draft', '!=', true);
                    })
                    // Override exists → check tentacle_video.draft
                        ->orWhereHas('tentacleVideos', fn ($tv) => $tv
                            ->where('tentacle_id', $tentacleId)
                            ->where('draft', '!=', true)
                        );
                })
            );
    }

    public function scopeFromLocalNationalities($query)
    {
        $zone = config('app.zone');
        $nationalities = config("app.zone_{$zone}_nationalities", []);

        return $query->whereIn('nationality_iso', $nationalities);
    }

    public function shouldBeSearchable(): bool
    {
        $zone = config('app.zone');
        $nationalities = config("app.zone_{$zone}_nationalities", []);
        $tentacleId = config('app.tentacle_id');

        // Channel must be from local nationalities
        if (! in_array($this->nationality_iso, $nationalities)) {
            return false;
        }

        // Must have at least one valid video
        return $this->videos()
            ->where('is_published', true)
            ->whereJsonContains('available_zones', $zone)
            ->where(function ($q) use ($tentacleId) {
                // No override → check videos.draft
                $q->where(function ($inner) use ($tentacleId) {
                    $inner->whereDoesntHave('tentacleVideos', fn ($tv) => $tv->where('tentacle_id', $tentacleId))
                        ->where('draft', '!=', true);
                })
                // Override exists → check tentacle_video.draft
                    ->orWhereHas('tentacleVideos', fn ($tv) => $tv
                        ->where('tentacle_id', $tentacleId)
                        ->where('draft', '!=', true)
                    );
            })
            ->exists();
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing('aliases');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'profile_picture_url' => $this->profile_picture_url,
            'card_picture_url' => $this->card_picture_url,
            'nationality_iso' => $this->nationality_iso,
            'aliases' => $this->aliases->pluck('alias')->toArray(),
        ];
    }

    protected function makeAllSearchableUsing($query)
    {
        return $query->with('aliases');
    }
}
