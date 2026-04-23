<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class OrderAnalyzer
{
    public function buildMenuTypeMap(array $menus): array
    {
        // Menu dipetakan dari data /api/menus agar klasifikasi lebih stabil dibanding keyword murni.
        $map = [];

        foreach ($menus as $menu) {
            $menuName = strtolower(trim((string) Arr::get($menu, 'name', '')));
            if ($menuName === '') {
                continue;
            }

            $categoryName = strtolower((string) Arr::get($menu, 'category.name', ''));
            $map[$menuName] = $this->classifyByCategory($categoryName) ?? $this->classifyByName($menuName);
        }

        return $map;
    }

    public function analyzeOrder(array $order, array $menuTypeMap, int $waitingCount): array
    {
        return $this->analyzeOrderWithContext($order, $menuTypeMap, $waitingCount, []);
    }

    public function analyzeOrderWithContext(array $order, array $menuTypeMap, int $waitingCount, array $context): array
    {
        $items = (array) Arr::get($order, 'items', []);

        // 1) Base complexity score by item type (drink=1, fried=2, heavy=3).
        [$totalScore, $totalQty, $typeCounts] = $this->calculateBaseComplexity($items, $menuTypeMap);
        $averageScore = $totalQty > 0 ? $totalScore / $totalQty : 3.0;

        // 2) Kitchen load factor from waiting queue volume.
        $kitchenStatus = $this->detectKitchenStatus($waitingCount);
        $kitchenLoadFactor = $this->kitchenLoadFactor($kitchenStatus);

        // 3) Aging fairness boost (older order gets gradual priority boost).
        $waitingMinutes = $this->extractWaitingMinutes($order);
        $agingBoost = $this->calculateAgingBoost($waitingMinutes);

        // 4) Batch optimization boost (same menu in close time window).
        $batchOrders = (array) Arr::get($context, 'orders', []);
        $batchWindowMinutes = max(
            1,
            (int) Arr::get($context, 'batch_window_minutes', (int) config('services.service_a.batch_window_minutes', 5))
        );
        $batchBoost = $this->calculateBatchBoost($order, $batchOrders, $batchWindowMinutes);

        // 5) Deadline pressure boost (close to SLA/estimated delay gets higher urgency).
        $estimatedCookMinutes = $this->estimateCookingMinutes($averageScore, $totalQty, $kitchenLoadFactor);
        $deadlineBoost = $this->calculateDeadlineBoost($waitingMinutes, $estimatedCookMinutes, $kitchenStatus);

        // 6) Small deterministic jitter to avoid rigid ties, still explainable and stable.
        $jitter = $this->deterministicJitter($order);

        // 7) Final multi-factor score with fairness and realism.
        $complexityPenalty = $averageScore * (float) config('services.service_a.complexity_penalty_multiplier', 2.6);
        $baseAnchor = (float) config('services.service_a.base_score_anchor', 12);

        $finalScore = ($baseAnchor - $complexityPenalty)
            + $agingBoost
            + $batchBoost
            + $deadlineBoost
            + $jitter;

        $uniqueTypes = count(array_filter($typeCounts, fn (int $count) => $count > 0));
        $priority = $this->determinePriorityFromFinalScore($finalScore, $uniqueTypes);
        $reason = $this->buildReason(
            priority: $priority,
            kitchenStatus: $kitchenStatus,
            typeCounts: $typeCounts,
            averageScore: $averageScore,
            uniqueTypes: $uniqueTypes,
            waitingMinutes: $waitingMinutes,
            agingBoost: $agingBoost,
            batchBoost: $batchBoost,
            deadlineBoost: $deadlineBoost,
            estimatedCookMinutes: $estimatedCookMinutes,
            finalScore: $finalScore,
        );

        return [
            'priority' => $priority,
            'kitchen_status' => $kitchenStatus,
            'score' => round($totalScore, 2),
            'average_score' => round($averageScore, 2),
            'final_score' => round($finalScore, 2),
            'reason' => $reason,
            'external_status' => $this->mapPriorityToExternalStatus($priority, $kitchenStatus),
            'external_note' => $reason,
            'type_counts' => $typeCounts,
            'waiting_minutes' => $waitingMinutes,
            'estimated_cook_minutes' => $estimatedCookMinutes,
            'batch_boost' => round($batchBoost, 2),
            'aging_boost' => round($agingBoost, 2),
            'deadline_boost' => round($deadlineBoost, 2),
        ];
    }

    protected function calculateBaseComplexity(array $items, array $menuTypeMap): array
    {
        $totalScore = 0.0;
        $totalQty = 0;
        $typeCounts = ['drink' => 0, 'fried' => 0, 'heavy' => 0];

        foreach ($items as $item) {
            $itemName = strtolower(trim((string) Arr::get($item, 'item_name', '')));
            $qty = max(1, (int) Arr::get($item, 'qty', 1));
            $type = $menuTypeMap[$itemName] ?? $this->classifyByName($itemName);
            $weight = $this->weightForType($type);

            $totalScore += $weight * $qty;
            $totalQty += $qty;
            $typeCounts[$type] += $qty;
        }

        if ($totalQty === 0) {
            $totalQty = 1;
            $typeCounts['heavy'] = 1;
            $totalScore = 3.0;
        }

        return [$totalScore, $totalQty, $typeCounts];
    }

    protected function classifyByCategory(string $categoryName): ?string
    {
        if ($categoryName === '') {
            return null;
        }

        if (str_contains($categoryName, 'minum') || str_contains($categoryName, 'drink') || str_contains($categoryName, 'beverage')) {
            return 'drink';
        }

        if (str_contains($categoryName, 'goreng') || str_contains($categoryName, 'fried')) {
            return 'fried';
        }

        if (str_contains($categoryName, 'makanan') || str_contains($categoryName, 'food')) {
            return 'heavy';
        }

        return null;
    }

    protected function classifyByName(string $name): string
    {
        $drinkKeywords = ['es ', 'teh', 'kopi', 'jus', 'soda', 'air', 'drink'];
        foreach ($drinkKeywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'drink';
            }
        }

        $friedKeywords = ['goreng', 'fried', 'kentang', 'crispy'];
        foreach ($friedKeywords as $keyword) {
            if (str_contains($name, $keyword)) {
                return 'fried';
            }
        }

        return 'heavy';
    }

    protected function weightForType(string $type): int
    {
        return match ($type) {
            'drink' => 1,
            'fried' => 2,
            default => 3,
        };
    }

    protected function determinePriorityFromFinalScore(float $finalScore, int $uniqueTypes): string
    {
        // Mixed items stay medium by policy to balance quick/slow components.
        if ($uniqueTypes > 1) {
            return 'medium';
        }

        if ($finalScore >= (float) config('services.service_a.priority_high_threshold', 9)) {
            return 'high';
        }

        if ($finalScore >= (float) config('services.service_a.priority_medium_threshold', 7)) {
            return 'medium';
        }

        return 'low';
    }

    protected function mapPriorityToExternalStatus(string $priority, string $kitchenStatus): string
    {
        if ($priority === 'high') {
            return 'processing';
        }

        if ($priority === 'medium' && in_array($kitchenStatus, ['normal', 'busy'], true)) {
            return 'processing';
        }

        return 'waiting';
    }

    protected function kitchenLoadFactor(string $kitchenStatus): float
    {
        return match ($kitchenStatus) {
            'overload' => 1.25,
            'busy' => 1.10,
            default => 1.00,
        };
    }

    protected function extractWaitingMinutes(array $order): int
    {
        $reference = Arr::get($order, 'created_at')
            ?: Arr::get($order, 'service_a_created_at')
            ?: Arr::get($order, 'external_updated_at');

        if (! is_string($reference) || trim($reference) === '') {
            return 0;
        }

        try {
            return max(0, Carbon::parse($reference)->diffInMinutes(now()));
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function calculateAgingBoost(int $waitingMinutes): float
    {
        // Soft fairness increase every 5 minutes, capped to avoid hard override.
        $perFiveMinutes = (float) config('services.service_a.aging_boost_per_5m', 0.35);
        $cap = (float) config('services.service_a.aging_boost_cap', 4);

        return min($cap, ($waitingMinutes / 5.0) * $perFiveMinutes);
    }

    protected function calculateBatchBoost(array $order, array $orders, int $batchWindowMinutes): float
    {
        if ($orders === []) {
            return 0.0;
        }

        $currentMenus = $this->extractNormalizedMenus($order);
        if ($currentMenus === []) {
            return 0.0;
        }

        $currentTime = $this->extractOrderTime($order);
        if (! $currentTime) {
            return 0.0;
        }

        $matches = 0;

        foreach ($orders as $candidate) {
            $candidateId = (string) Arr::get($candidate, 'id', '');
            $currentId = (string) Arr::get($order, 'id', '');
            if ($candidateId !== '' && $candidateId === $currentId) {
                continue;
            }

            $candidateTime = $this->extractOrderTime((array) $candidate);
            if (! $candidateTime) {
                continue;
            }

            if (abs($candidateTime->diffInMinutes($currentTime)) > $batchWindowMinutes) {
                continue;
            }

            $candidateMenus = $this->extractNormalizedMenus((array) $candidate);
            if ($candidateMenus === []) {
                continue;
            }

            if (count(array_intersect($currentMenus, $candidateMenus)) > 0) {
                $matches++;
            }
        }

        // Small boost only; fairness/aging still dominates.
        $perMatch = (float) config('services.service_a.batch_boost_per_match', 0.45);
        $cap = (float) config('services.service_a.batch_boost_cap', 1.8);

        return min($cap, $matches * $perMatch);
    }

    protected function extractNormalizedMenus(array $order): array
    {
        $menus = [];

        foreach ((array) Arr::get($order, 'items', []) as $item) {
            $name = strtolower(trim((string) Arr::get($item, 'item_name', '')));
            if ($name !== '') {
                $menus[] = $name;
            }
        }

        return array_values(array_unique($menus));
    }

    protected function extractOrderTime(array $order): ?Carbon
    {
        $reference = Arr::get($order, 'created_at')
            ?: Arr::get($order, 'service_a_created_at')
            ?: Arr::get($order, 'external_updated_at');

        if (! is_string($reference) || trim($reference) === '') {
            return null;
        }

        try {
            return Carbon::parse($reference);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function estimateCookingMinutes(float $averageScore, int $totalQty, float $kitchenLoadFactor): int
    {
        $baseMinutes = ($averageScore * max(1, $totalQty)) * 2.2;

        return (int) max(4, round($baseMinutes * $kitchenLoadFactor));
    }

    protected function calculateDeadlineBoost(int $waitingMinutes, int $estimatedCookMinutes, string $kitchenStatus): float
    {
        $targetSlaMinutes = max(10, (int) config('services.service_a.target_sla_minutes', 25));
        $projectedFinishMinutes = $waitingMinutes + $estimatedCookMinutes;
        $remainingToSla = $targetSlaMinutes - $projectedFinishMinutes;

        if ($remainingToSla <= 0) {
            return (float) config('services.service_a.deadline_boost_late', 3);
        }

        if ($remainingToSla <= 5) {
            return $kitchenStatus === 'overload'
                ? (float) config('services.service_a.deadline_boost_near_overload', 2.4)
                : (float) config('services.service_a.deadline_boost_near', 2);
        }

        if ($remainingToSla <= 10) {
            return (float) config('services.service_a.deadline_boost_warning', 1.2);
        }

        return 0.0;
    }

    protected function deterministicJitter(array $order): float
    {
        $seed = (string) (Arr::get($order, 'id')
            ?: Arr::get($order, 'order_code')
            ?: json_encode(Arr::get($order, 'items', []))
            ?: 'seed');

        $steps = max(3, (int) config('services.service_a.jitter_steps', 11));
        if ($steps % 2 === 0) {
            $steps++;
        }

        $half = intdiv($steps - 1, 2);
        $value = abs(crc32($seed)) % $steps; // 0..steps-1
        $scale = (float) config('services.service_a.jitter_scale', 0.01);

        return ($value - $half) * $scale;
    }

    protected function detectKitchenStatus(int $waitingCount): string
    {
        $overloadThreshold = (int) config('services.service_a.overload_threshold', 10);
        $busyThreshold = (int) config('services.service_a.busy_threshold', 5);

        if ($waitingCount > $overloadThreshold) {
            return 'overload';
        }

        if ($waitingCount > $busyThreshold) {
            return 'busy';
        }

        return 'normal';
    }

    protected function buildReason(
        string $priority,
        string $kitchenStatus,
        array $typeCounts,
        float $averageScore,
        int $uniqueTypes,
        int $waitingMinutes,
        float $agingBoost,
        float $batchBoost,
        float $deadlineBoost,
        int $estimatedCookMinutes,
        float $finalScore,
    ): string
    {
        $parts = [];

        if ($uniqueTypes > 1) {
            $parts[] = 'Order campuran, item cepat diprioritaskan lebih dulu';
        } elseif (($typeCounts['drink'] ?? 0) > 0) {
            $parts[] = 'Diprioritaskan karena dominan minuman (cepat disajikan)';
        } elseif (($typeCounts['fried'] ?? 0) > 0) {
            $parts[] = 'Komposisi gorengan dengan waktu masak sedang';
        } else {
            $parts[] = 'Komposisi makanan berat dengan estimasi masak lebih lama';
        }

        $parts[] = 'Waktu tunggu '.$waitingMinutes.' menit (aging +'.number_format($agingBoost, 2).')';

        if ($batchBoost > 0) {
            $parts[] = 'Batch cooking terdeteksi (boost +'.number_format($batchBoost, 2).')';
        } else {
            $parts[] = 'Tidak ada batch cooking relevan dalam jendela waktu dekat';
        }

        if ($deadlineBoost > 0) {
            $parts[] = 'Mendekati deadline masak (boost +'.number_format($deadlineBoost, 2).')';
        } else {
            $parts[] = 'Deadline masih aman';
        }

        $parts[] = 'Estimasi masak '.$estimatedCookMinutes.' menit';
        $parts[] = 'Prioritas '.$priority.' (avg '.number_format($averageScore, 2).', final '.number_format($finalScore, 2).')';
        $parts[] = 'Kondisi dapur '.$kitchenStatus;

        return implode('. ', $parts).'.';
    }
}
