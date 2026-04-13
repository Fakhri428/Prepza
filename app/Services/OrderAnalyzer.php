<?php

namespace App\Services;

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
        // Rule-based scoring: drink=1, fried=2, heavy=3.
        $items = Arr::get($order, 'items', []);
        $totalScore = 0;
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

        $uniqueTypes = count(array_filter($typeCounts, fn (int $count) => $count > 0));
        $averageScore = $totalQty > 0 ? $totalScore / $totalQty : 3;
        $kitchenStatus = $this->detectKitchenStatus($waitingCount);

        $priority = $this->determinePriority($averageScore, $uniqueTypes);
        $reason = $this->buildReason($priority, $kitchenStatus, $typeCounts, $averageScore, $uniqueTypes);

        return [
            'priority' => $priority,
            'kitchen_status' => $kitchenStatus,
            'score' => $totalScore,
            'average_score' => round($averageScore, 2),
            'reason' => $reason,
            'external_status' => $this->mapPriorityToExternalStatus($priority, $kitchenStatus),
            'external_note' => $reason,
            'type_counts' => $typeCounts,
        ];
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

    protected function determinePriority(float $averageScore, int $uniqueTypes): string
    {
        // Sesuai requirement: mixed items selalu medium agar item cepat tetap diprioritaskan dahulu.
        if ($uniqueTypes > 1) {
            return 'medium';
        }

        if ($averageScore <= 1.5) {
            return 'high';
        }

        if ($averageScore <= 2.4) {
            return 'medium';
        }

        return 'low';
    }

    protected function mapPriorityToExternalStatus(string $priority, string $kitchenStatus): string
    {
        if ($priority === 'high') {
            return 'processing';
        }

        if ($priority === 'medium' && $kitchenStatus === 'normal') {
            return 'processing';
        }

        return 'waiting';
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

    protected function buildReason(string $priority, string $kitchenStatus, array $typeCounts, float $averageScore, int $uniqueTypes): string
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

        $parts[] = 'Prioritas '.$priority.' (rata-rata skor '.$averageScore.')';
        $parts[] = 'Kondisi dapur '.$kitchenStatus;

        return implode('. ', $parts).'.';
    }
}
