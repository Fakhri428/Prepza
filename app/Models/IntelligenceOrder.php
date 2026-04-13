<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntelligenceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_a_order_id',
        'order_code',
        'customer_name',
        'gender',
        'status',
        'external_status',
        'external_note',
        'external_updated_at',
        'total_amount',
        'queue_number',
        'queue_status',
        'service_a_created_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'external_updated_at' => 'datetime',
            'service_a_created_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(IntelligenceOrderItem::class);
    }
}
