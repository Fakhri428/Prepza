<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class TrendInsightService
{
    public function buildTrendPayload(array $orders): ?array
    {
        $payloads = $this->buildTrendPayloadsByGender($orders);

        return $payloads[0] ?? null;
    }

    public function buildTrendPayloadsByGender(array $orders): array
    {
        $totalsByGender = [
            'male' => [],
            'female' => [],
        ];
        $totalsOverall = [];
        $hasGenderData = false;

        foreach ($orders as $order) {
            $gender = $this->resolveGender((string) Arr::get($order, 'gender', ''));

            foreach ((array) Arr::get($order, 'items', []) as $item) {
                $name = trim((string) Arr::get($item, 'item_name', ''));
                if ($name === '') {
                    continue;
                }

                $normalized = mb_strtolower($name);
                $qty = max(1, (int) Arr::get($item, 'qty', 1));

                $totalsOverall[$normalized] = ($totalsOverall[$normalized] ?? 0) + $qty;

                if ($gender === null) {
                    continue;
                }

                $hasGenderData = true;
                $totalsByGender[$gender][$normalized] = ($totalsByGender[$gender][$normalized] ?? 0) + $qty;
            }
        }

        $minimumRepeat = (int) config('services.service_a.trend_min_repeat', 4);
        $minimumRepeatGender = (int) config('services.service_a.trend_min_repeat_gender', 2);
        $now = Carbon::now();
        $expiresAt = $now->copy()->addMinutes((int) config('services.service_a.trend_expire_minutes', 180));

        $payloads = [];

        foreach (['male' => 'laki-laki', 'female' => 'perempuan'] as $gender => $genderLabel) {
            $totals = $totalsByGender[$gender] ?? [];
            if ($totals === []) {
                continue;
            }

            arsort($totals);
            $topItem = (string) array_key_first($totals);
            $topCount = (int) $totals[$topItem];

            if ($topCount < $minimumRepeatGender) {
                continue;
            }

            $displayName = mb_convert_case($topItem, MB_CASE_TITLE, 'UTF-8');
            $score = min(100, 50 + ($topCount * 5));

            $payloads[] = [
                'title' => $displayName.' Sedang Naik ('.$genderLabel.')',
                'image_url' => (string) config('services.service_a.trend_placeholder_image'),
                'caption' => "Terdeteksi {$topCount} porsi aktif untuk {$displayName} dari pelanggan {$genderLabel} pada antrean saat ini.",
                'score' => $score,
                'source_timestamp' => $now->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
                'is_active' => true,
            ];
        }

        if ($payloads === [] && ! $hasGenderData && $totalsOverall !== []) {
            arsort($totalsOverall);
            $topItem = (string) array_key_first($totalsOverall);
            $topCount = (int) $totalsOverall[$topItem];

            if ($topCount >= $minimumRepeat) {
                $displayName = mb_convert_case($topItem, MB_CASE_TITLE, 'UTF-8');
                $score = min(100, 50 + ($topCount * 5));

                $payloads[] = [
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

        return $payloads;
    }

    protected function resolveGender(string $gender): ?string
    {
        $normalized = strtolower(trim($gender));

        if (in_array($normalized, ['male', 'm', 'laki-laki', 'laki laki', 'pria'], true)) {
            return 'male';
        }

        if (in_array($normalized, ['female', 'f', 'perempuan', 'wanita'], true)) {
            return 'female';
        }

        return null;
    }
}
