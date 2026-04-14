<?php

namespace App\Http\Controllers;

use App\Models\IntelligenceMenu;
use App\Models\IntelligenceOrder;
use App\Models\IntelligenceTrend;
use App\Services\OrderAnalyzer;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(OrderAnalyzer $analyzer)
    {
        $orders = IntelligenceOrder::query()
            ->with('items')
            ->latest('last_synced_at')
            ->limit(300)
            ->get();

        $menus = IntelligenceMenu::query()
            ->with('category:id,name')
            ->get()
            ->map(fn (IntelligenceMenu $menu) => [
                'name' => $menu->name,
                'category' => [
                    'name' => (string) optional($menu->category)->name,
                ],
            ])
            ->all();

        $menuTypeMap = $analyzer->buildMenuTypeMap($menus);

        $activeStatuses = ['queued', 'waiting', 'processing'];
        $activeOrders = $orders->filter(fn (IntelligenceOrder $order) => in_array(strtolower((string) $order->status), $activeStatuses, true));
        $waitingCount = $activeOrders->count();

        $priorityCounts = ['high' => 0, 'medium' => 0, 'low' => 0];

        foreach ($activeOrders as $order) {
            $payload = [
                'items' => $order->items->map(fn ($item) => [
                    'item_name' => $item->item_name,
                    'qty' => (int) $item->qty,
                ])->all(),
            ];

            $analysis = $analyzer->analyzeOrder($payload, $menuTypeMap, $waitingCount);
            $priority = (string) Arr::get($analysis, 'priority', 'low');
            $priorityCounts[$priority] = ($priorityCounts[$priority] ?? 0) + 1;
        }

        $throughputByHour = collect(range(23, 0))
            ->map(function (int $hoursAgo) use ($orders): array {
                $hour = Carbon::now()->subHours($hoursAgo);
                $count = $orders->filter(function (IntelligenceOrder $order) use ($hour): bool {
                    $timestamp = $order->external_updated_at ?? $order->last_synced_at;

                    return $timestamp !== null
                        && $timestamp->format('Y-m-d H') === $hour->format('Y-m-d H');
                })->count();

                return [
                    'label' => $hour->format('H'),
                    'count' => $count,
                ];
            })
            ->values();

        $maxHourlyCount = max(1, (int) $throughputByHour->max('count'));
        $throughputChart = $throughputByHour->map(fn (array $point) => [
            'label' => $point['label'],
            'count' => $point['count'],
            'height' => (int) round(($point['count'] / $maxHourlyCount) * 100),
        ])->all();

        $statusLabels = [
            'waiting' => 'Waiting',
            'processing' => 'Processing',
            'done' => 'Done',
            'cancelled' => 'Cancelled',
        ];

        $statusCounts = collect(array_keys($statusLabels))
            ->mapWithKeys(fn (string $status) => [
                $status => $orders->where('status', $status)->count(),
            ]);

        $statusTotal = max(1, (int) $statusCounts->sum());
        $statusDistribution = collect(array_keys($statusLabels))
            ->map(fn (string $status) => [
                'label' => $statusLabels[$status],
                'count' => (int) ($statusCounts[$status] ?? 0),
                'percentage' => (int) round((($statusCounts[$status] ?? 0) / $statusTotal) * 100),
                'color' => match ($status) {
                    'waiting' => '#f59e0b',
                    'processing' => '#6c63ff',
                    'done' => '#10b981',
                    default => '#ef4444',
                },
            ])
            ->all();

        $completedToday = IntelligenceOrder::query()
            ->where('status', 'done')
            ->whereDate('external_updated_at', today())
            ->count();

        $orderTodayCount = IntelligenceOrder::query()
            ->whereDate('service_a_created_at', today())
            ->count();

        $orderMonthCount = IntelligenceOrder::query()
            ->whereYear('service_a_created_at', now()->year)
            ->whereMonth('service_a_created_at', now()->month)
            ->count();

        $dailyRevenue = (float) IntelligenceOrder::query()
            ->where('status', 'done')
            ->whereDate('external_updated_at', today())
            ->sum('total_amount');

        $monthlyRevenue = (float) IntelligenceOrder::query()
            ->where('status', 'done')
            ->whereYear('external_updated_at', now()->year)
            ->whereMonth('external_updated_at', now()->month)
            ->sum('total_amount');

        $activeTrends = IntelligenceTrend::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $syncedMenus = IntelligenceMenu::query()->count();
        $totalMenus = max(1, $syncedMenus);

        $engineMetrics = [
            ['label' => 'Sinkronisasi Order Aktif', 'percentage' => min(100, (int) round(($activeOrders->count() / max(1, $orders->count())) * 100))],
            ['label' => 'Order Selesai Hari Ini', 'percentage' => min(100, (int) round(($completedToday / max(1, $orders->count())) * 100))],
            ['label' => 'Trend Aktif', 'percentage' => min(100, (int) round(($activeTrends / max(1, IntelligenceTrend::query()->count())) * 100))],
            ['label' => 'Katalog Menu Tersinkron', 'percentage' => min(100, (int) round(($syncedMenus / $totalMenus) * 100))],
            ['label' => 'Antrian Sedang Diproses', 'percentage' => min(100, (int) round((($statusCounts['processing'] ?? 0) / $statusTotal) * 100))],
        ];

        return view('dashboard', [
            'stats' => [
                'total_orders' => $activeOrders->count(),
                'processing_orders' => (int) ($statusCounts['processing'] ?? 0),
                'high_priority' => (int) ($priorityCounts['high'] ?? 0),
                'completed_today' => $completedToday,
                'daily_revenue' => $dailyRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'orders_today' => $orderTodayCount,
                'orders_month' => $orderMonthCount,
            ],
            'priorityCounts' => $priorityCounts,
            'throughputChart' => $throughputChart,
            'statusDistribution' => $statusDistribution,
            'engineMetrics' => $engineMetrics,
        ]);
    }
}
