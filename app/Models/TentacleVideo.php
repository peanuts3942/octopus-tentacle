<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TentacleVideo extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'tentacle_video';

    protected $fillable = [
        'tentacle_id',
        'video_id',
        'draft',
    ];

    protected function casts(): array
    {
        return [
            'draft' => 'boolean',
        ];
    }

    public function tentacle(): BelongsTo
    {
        return $this->belongsTo(Tentacle::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Determine the effective draft status for this video in this tentacle.
     *
     * If a tentacle_video entry exists, its draft value takes precedence.
     * Otherwise, the video's own draft value is used.
     */
    public function getEffectiveDraftAttribute(): bool
    {
        return $this->draft;
    }

    /**
     * Check if the video should be visible (not draft) for this tentacle.
     */
    public function isVisible(): bool
    {
        return ! $this->draft;
    }
}
