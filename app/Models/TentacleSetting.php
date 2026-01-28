<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TentacleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tentacle_id',
        'name',
        'options',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tentacle(): BelongsTo
    {
        return $this->belongsTo(Tentacle::class);
    }
}
