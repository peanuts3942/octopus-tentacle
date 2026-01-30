<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TextSeoTranslation extends Model
{
    protected $table = 'textseo_translations';

    protected $fillable = [
        'textseo_id',
        'locale',
        'meta_title',
        'meta_description',
        'h1',
        'h2',
        'breadcrumb',
        'image_url',
    ];

    public function textSeo(): BelongsTo
    {
        return $this->belongsTo(TextSeo::class, 'textseo_id');
    }
}
