<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    use HasFactory;

    protected $casts = [
        'draft' => 'boolean',
    ];

    public function tentacleVideos(): HasMany
    {
        return $this->hasMany(TentacleVideo::class);
    }

    /**
     * Get the effective draft status for a specific tentacle.
     *
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
}
