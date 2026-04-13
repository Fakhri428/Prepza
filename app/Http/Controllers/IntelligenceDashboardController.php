<?php

namespace App\Http\Controllers;

use App\Models\IntelligenceOrder;
use App\Services\OrderAnalyzer;
use App\Services\TrendInsightService;
use Illuminate\Support\Arr;
use Throwable;

class IntelligenceDashboardController extends Controller
{
    public function __invoke(
        OrderAnalyzer $analyzer,
        TrendInsightService $trendInsightService,
    ) {
        try {
            $menus = [];
            $orders = $this->loadOrdersFromLocalDb();

            $menuTypeMap = $analyzer->buildMenuTypeMap($menus);
            $waitingCount = $this->countWaitingOrders($orders);

            $analyzedOrders = [];
            $priorityCounts = ['high' => 0, 'medium' => 0, 'low' => 0];
            $kitchenStatus = 'normal';

            foreach ($orders as $order) {
                $analysis = $analyzer->analyzeOrder($order, $menuTypeMap, $waitingCount);
                $priorityCounts[$analysis['priority']]++;
                $kitchenStatus = $analysis['kitchen_status'];

                $analyzedOrders[] = [
                    'id' => (int) Arr::get($order, 'id'),
                    'order_code' => (string) Arr::get($order, 'order_code', '-'),
                    'customer_name' => (string) Arr::get($order, 'customer_name', '-'),
                    'status' => (string) Arr::get($order, 'status', '-'),
                    'external_status' => (string) Arr::get($order, 'external_status', '-'),
                    'item_count' => $this->countOrderItems((array) Arr::get($order, 'items', [])),
                    'priority' => $analysis['priority'],
                    'reason' => $analysis['reason'],
                ];
            }

            $trendPayload = $trendInsightService->buildTrendPayload($orders);

            $errorMessage = null;
            if ($orders === []) {
                $errorMessage = 'Belum ada data lokal. Jalankan command queue:process-orders untuk sinkronisasi dari Service A.';
            }

            return view('intelligence.dashboard', [
                'errorMessage' => $errorMessage,
                'summary' => [
                    'total_orders' => count($orders),
                    'waiting_count' => $waitingCount,
                    'kitchen_status' => $kitchenStatus,
                    'priority_counts' => $priorityCounts,
                ],
                'orders' => $analyzedOrders,
                'trend' => $trendPayload,
            ]);
        } catch (Throwable $throwable) {
            return view('intelligence.dashboard', [
                'errorMessage' => $throwable->getMessage(),
                'summary' => [
                    'total_orders' => 0,
                    'waiting_count' => 0,
                    'kitchen_status' => 'normal',
                    'priority_counts' => ['high' => 0, 'medium' => 0, 'low' => 0],
                ],
                'orders' => [],
                'trend' => null,
            ]);
        }
    }

    protected function countWaitingOrders(array $orders): int
    {
        $total = 0;

        foreach ($orders as $order) {
            $status = strtolower((string) Arr::get($order, 'status', ''));
            if (in_array($status, ['queued', 'waiting', 'processing'], true)) {
                $total++;
            }
        }

        return $total;
    }

    protected function countOrderItems(array $items): int
    {
        $total = 0;

        foreach ($items as $item) {
            $total += max(1, (int) Arr::get($item, 'qty', 1));
        }

        return $total;
    }

    protected function loadOrdersFromLocalDb(): array
    {
        $localOrders = IntelligenceOrder::query()
            ->with('items')
            ->latest('last_synced_at')
            ->limit(100)
            ->get();

        return $localOrders->map(function (IntelligenceOrder $order) {
            return [
                'id' => $order->service_a_order_id,
                'order_code' => $order->order_code,
                'customer_name' => $order->customer_name,
                'status' => $order->status,
                'external_status' => $order->external_status,
                'external_note' => $order->external_note,
                'created_at' => optional($order->service_a_created_at)?->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->service_a_item_id,
                    'item_name' => $item->item_name,
                    'note' => $item->note,
                    'qty' => $item->qty,
                    'subtotal' => $item->subtotal,
                ])->toArray(),
                'queue' => [
                    'queue_number' => $order->queue_number,
                    'status' => $order->queue_status,
                ],
            ];
        })->all();
    }
}
