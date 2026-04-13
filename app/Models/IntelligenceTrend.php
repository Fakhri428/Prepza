<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntelligenceTrend extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_run_id',
        'title',
        'image_url',
        'caption',
        'score',
        'source_timestamp',
        'expires_at',
        'is_active',
        'sent_to_service_a',
        'payload',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'source_timestamp' => 'datetime',
            'expires_at' => 'datetime',
            'detected_at' => 'datetime',
            'is_active' => 'boolean',
            'sent_to_service_a' => 'boolean',
            'payload' => 'array',
        ];
    }
}
