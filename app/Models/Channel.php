<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'profile_picture_url' => $this->profile_picture_url,
            'card_picture_url' => $this->card_picture_url,
        ];
    }
}
