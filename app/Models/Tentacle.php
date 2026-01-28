<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tentacle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(TentacleSetting::class);
    }

    public function tentacleVideos(): HasMany
    {
        return $this->hasMany(TentacleVideo::class);
    }
}
