<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelTranslation extends Model
{
    protected $fillable = [
        'channel_id',
        'locale',
        'short_description',
        'long_description',
        'alt_thumbnail',
        'alt_banner',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
