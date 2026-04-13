<?php

namespace App\Http\Controllers;

use App\Services\OrderAnalyzer;
use App\Services\ServiceAApiService;
use App\Services\TrendInsightService;
use Illuminate\Support\Arr;
use Throwable;

class IntelligenceDashboardController extends Controller
{
    public function __invoke(
        ServiceAApiService $apiService,
        OrderAnalyzer $analyzer,
        TrendInsightService $trendInsightService,
    ) {
        try {
            $menus = $apiService->fetchMenus();
            $orders = $apiService->fetchQueueOrders();
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

            return view('intelligence.dashboard', [
                'errorMessage' => null,
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
}
