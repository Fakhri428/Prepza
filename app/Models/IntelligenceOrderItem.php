<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntelligenceOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'intelligence_order_id',
        'service_a_item_id',
        'item_name',
        'note',
        'qty',
        'subtotal',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'subtotal' => 'decimal:2',
            'last_synced_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(IntelligenceOrder::class, 'intelligence_order_id');
    }
}
