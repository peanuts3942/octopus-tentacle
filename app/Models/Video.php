<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Video extends Model
{
    use HasFactory, Searchable;

    public const DRAFT_STATUS = 1;

    protected $fillable = [
        'channel_id',
        'title',
        'slug',
        'thumbnail_url',
        'preview_url',
        'time',
        'player_url',
        'draft',
        'published_at',
        'views',
        'likes',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'draft' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'views' => 'integer',
            'likes' => 'integer',
            'time' => 'integer',
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

    /**
     * Get the effective draft status for a specific tentacle.
     * If a tentacle_video override exists, use its draft value.
     * Otherwise, fall back to the video's own draft value.
     */
    public function isDraftForTentacle(Tentacle $tentacle): bool
    {
        $override = $this->tentacleVideos()
            ->where('tentacle_id', $tentacle->id)
            ->first();

        if ($override) {
            return $override->draft;
        }

        return $this->draft;
    }

    // MeiliSearch
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
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
            ] : null,
            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->toArray(),
        ];
    }

    /**
     * Modify the query used to retrieve models when making all searchable.
     */
    protected function makeAllSearchableUsing($query)
    {
        return $query->with(['channel', 'tags']);
    }
}
