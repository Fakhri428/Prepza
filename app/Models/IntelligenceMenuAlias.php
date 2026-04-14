<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntelligenceMenuAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_a_alias_id',
        'intelligence_menu_id',
        'alias',
        'normalized_alias',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(IntelligenceMenu::class, 'intelligence_menu_id');
    }
}
