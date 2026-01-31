<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Video extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    public const DRAFT_STATUS = 1;

    protected $fillable = [
        'channel_id',
        'thumbnail_url',
        'preview_bunny_url',
        'time',
        'player_bunny_url',
        'player_babastream_url',
        'draft',
        'published_at',
        'views',
        'likes',
        'is_published',
        'available_zones',
    ];

    protected $appends = ['title', 'slug', 'preview_url', 'player_url'];

    protected function casts(): array
    {
        return [
            'draft' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'views' => 'integer',
            'likes' => 'integer',
            'time' => 'integer',
            'available_zones' => 'array',
        ];
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('draft', '!=', self::DRAFT_STATUS);
    }

    public function scopeWithChannel($query)
    {
        return $query->with(['channel:id,name,slug,profile_picture_url']);
    }

    public function scopeAvailableInZone($query, $zone = null)
    {
        $zone = $zone ?? config('app.zone');

        return $query->whereJsonContains('available_zones', $zone);
    }

    public function isAvailableInZone($zone = null): bool
    {
        $zone = $zone ?? config('app.zone');

        return in_array($zone, $this->available_zones ?? []);
    }

    public function scopeNotDraftForTentacle($query)
    {
        $tentacleId = config('app.tentacle_id');

        return $query
            ->leftJoin('tentacle_video', function ($join) use ($tentacleId) {
                $join->on('videos.id', '=', 'tentacle_video.video_id')
                    ->where('tentacle_video.tentacle_id', '=', $tentacleId);
            })
            ->where(function ($q) {
                // No override → use videos.draft
                $q->where(function ($inner) {
                    $inner->whereNull('tentacle_video.id')
                        ->where('videos.draft', '!=', true);
                    // Override exists → use tentacle_video.draft
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('tentacle_video.id')
                        ->where('tentacle_video.draft', '!=', true);
                });
            });
    }

    public function scopeIndexable($query)
    {
        $zone = config('app.zone');
        $nationalities = config("app.zone_{$zone}_nationalities", []);

        return $query
            ->select('videos.*')
            ->where('videos.is_published', true)
            ->notDraftForTentacle()
            ->whereJsonContains('videos.available_zones', $zone)
            ->whereHas('translations', fn ($q) => $q->where('locale', $zone))
            ->whereHas('channel', fn ($q) => $q->whereIn('nationality_iso', $nationalities));
    }

    public function scopeFromLocalNationalities($query)
    {
        $zone = config('app.zone');
        $nationalities = config("app.zone_{$zone}_nationalities", []);

        return $query
            ->join('channels', 'videos.channel_id', '=', 'channels.id')
            ->whereIn('channels.nationality_iso', $nationalities);
    }

    // Relations
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function tentacleVideos(): HasMany
    {
        return $this->hasMany(TentacleVideo::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(VideoTranslation::class);
    }

    public function translation(): HasOne
    {
        $locale = app()->getLocale();

        return $this->hasOne(VideoTranslation::class)
            ->where('locale', $locale)
            ->withDefault(fn () => $this->translations()->first());
    }

    // Accessors for translated fields
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation?->title ?? 'Untitled'
        );
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translation?->slug ?? $this->id
        );
    }

    protected function previewUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->preview_bunny_url
        );
    }

    protected function playerUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->player_babastream_url ?? $this->player_bunny_url
        );
    }

    /**
     * Get the effective draft status for a specific tentacle.
     * If a tentacle_video override exists, use its draft value.
     * Otherwise, fall back to the video's own draft value.
     */
    public function isDraftForTentacle(Tentacle $tentacle): bool
    {
        return $this->isDraftForTentacleId($tentacle->id);
    }

    /**
     * Get the effective draft status for the current tentacle (from config).
     */
    public function isDraftForCurrentTentacle(): bool
    {
        return $this->isDraftForTentacleId(config('app.tentacle_id'));
    }

    private function isDraftForTentacleId(int $tentacleId): bool
    {
        $override = $this->tentacleVideos()
            ->where('tentacle_id', $tentacleId)
            ->first();

        if ($override) {
            return (bool) $override->draft;
        }

        return (bool) $this->draft;
    }

    // MeiliSearch
    public function shouldBeSearchable(): bool
    {
        $zone = config('app.zone');
        $nationalities = config("app.zone_{$zone}_nationalities", []);

        // Must be published
        if (! $this->is_published) {
            return false;
        }

        // Must not be draft for current tentacle
        if ($this->isDraftForCurrentTentacle()) {
            return false;
        }

        // Must be available in current zone
        if (! $this->isAvailableInZone($zone)) {
            return false;
        }

        // Must have a translation for current zone
        if (! $this->translations()->where('locale', $zone)->exists()) {
            return false;
        }

        // Channel must be from local nationalities
        return $this->channel && in_array($this->channel->nationality_iso, $nationalities);
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['translation', 'channel.aliases', 'tags.translations']);
        $zone = config('app.zone');

        return [
            'id' => $this->id,
            'title' => $this->translation?->title ?? 'Untitled',
            'slug' => $this->translation?->slug ?? $this->id,
            'thumbnail_url' => $this->thumbnail_url,
            'preview_url' => $this->preview_url,
            'time' => $this->time,
            'views' => $this->views ?? 0,
            'likes' => $this->likes ?? 0,
            'published_at' => $this->published_at?->timestamp,
            'draft' => $this->draft,
            'is_published' => $this->is_published,
            'channel' => $this->channel ? [
                'id' => $this->channel->id,
                'name' => $this->channel->name,
                'slug' => $this->channel->slug,
                'profile_picture_url' => $this->channel->profile_picture_url ?? null,
                'nationality_iso' => $this->channel->nationality_iso ?? null,
                'aliases' => $this->channel->aliases->pluck('alias')->toArray(),
            ] : null,
            'tags' => $this->tags->map(function ($tag) use ($zone) {
                $translation = $tag->translations->where('locale', $zone)->first();

                return [
                    'id' => $tag->id,
                    'name' => $translation?->name ?? 'Unnamed',
                    'slug' => $translation?->slug ?? $tag->id,
                ];
            })->toArray(),
        ];
    }

    protected function makeAllSearchableUsing($query)
    {
        return $query->with(['channel.aliases', 'tags.translations', 'translation']);
    }
}
