<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoTranslation extends Model
{
    protected $fillable = [
        'video_id',
        'locale',
        'title',
        'meta_title',
        'slug',
        'short_description',
        'long_description',
        'alt_thumbnail',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
