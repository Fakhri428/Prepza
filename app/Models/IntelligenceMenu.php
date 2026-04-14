<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntelligenceMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_a_menu_id',
        'intelligence_category_id',
        'service_a_category_id',
        'name',
        'slug',
        'description',
        'image_path',
        'image_external_url',
        'image_url',
        'price',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IntelligenceCategory::class, 'intelligence_category_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(IntelligenceMenuAlias::class);
    }
}
