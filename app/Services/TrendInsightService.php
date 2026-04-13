<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class TrendInsightService
{
    public function buildTrendPayload(array $orders): ?array
    {
        $totals = [];

        foreach ($orders as $order) {
            foreach ((array) Arr::get($order, 'items', []) as $item) {
                $name = trim((string) Arr::get($item, 'item_name', ''));
                if ($name === '') {
                    continue;
                }

                $normalized = mb_strtolower($name);
                $qty = max(1, (int) Arr::get($item, 'qty', 1));
                $totals[$normalized] = ($totals[$normalized] ?? 0) + $qty;
            }
        }

        if ($totals === []) {
            return null;
        }

        arsort($totals);
        $topItem = (string) array_key_first($totals);
        $topCount = (int) $totals[$topItem];

        $minimumRepeat = (int) config('services.service_a.trend_min_repeat', 4);
        if ($topCount < $minimumRepeat) {
            return null;
        }

        $displayName = mb_convert_case($topItem, MB_CASE_TITLE, 'UTF-8');
        $score = min(100, 50 + ($topCount * 5));
        $now = Carbon::now();
        $expiresAt = $now->copy()->addMinutes((int) config('services.service_a.trend_expire_minutes', 180));

        return [
            'title' => $displayName.' Sedang Naik',
            'image_url' => (string) config('services.service_a.trend_placeholder_image'),
            'caption' => "Terdeteksi {$topCount} porsi aktif untuk {$displayName} pada antrean saat ini.",
            'score' => $score,
            'source_timestamp' => $now->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'is_active' => true,
        ];
    }
}
