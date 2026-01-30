<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TextSeo extends Model
{
    protected $table = 'textseo';

    protected $fillable = [
        'name',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(TextSeoTranslation::class, 'textseo_id');
    }
}
