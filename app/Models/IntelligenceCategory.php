<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntelligenceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_a_category_id',
        'name',
        'slug',
        'description',
        'is_active',
        'menu_count',
        'service_a_created_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'menu_count' => 'integer',
            'service_a_created_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function menus(): HasMany
    {
        return $this->hasMany(IntelligenceMenu::class);
    }
}
