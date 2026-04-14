<?php

namespace App\Http\Controllers;

use App\Exceptions\ServiceARequestException;
use App\Models\IntelligenceOrder;
use App\Models\IntelligenceTrend;
use App\Services\OrderAnalyzer;
use App\Services\ServiceAApiService;
use App\Services\TrendInsightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Throwable;

class IntelligenceDashboardController extends Controller
{
    public function updateStatus(Request $request, ServiceAApiService $apiService, IntelligenceOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'target_status' => ['required', 'in:processing,done'],
        ]);

        $targetStatus = (string) Arr::get($validated, 'target_status');

        try {
            $apiService->patchExternalUpdate((int) $order->service_a_order_id, [
                'external_status' => $targetStatus,
                'external_note' => 'Status diubah manual dari dashboard Service B.',
                'queue_status' => $targetStatus,
            ]);

            $order->external_status = $targetStatus;
            $order->queue_status = $targetStatus;
            $order->external_note = 'Status diubah manual dari dashboard Service B.';
            $order->external_updated_at = Carbon::now();

            if ($targetStatus === 'done') {
                $order->status = 'done';
            }

            $order->save();

            return back()->with('status', "Status order {$order->order_code} berhasil diubah ke {$targetStatus}.");
        } catch (ServiceARequestException $exception) {
            return back()->with('error', 'Gagal update ke Service A: '.$exception->getMessage());
        } catch (Throwable $throwable) {
            return back()->with('error', 'Gagal update status: '.$throwable->getMessage());
        }
    }

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

            $trendPayloads = $this->loadLatestStoredTrendsByGender();
            if ($trendPayloads === []) {
                $trendPayloads = $this->buildTrendGroupsFromPayloads(
                    $trendInsightService->buildTrendPayloadsByGender($orders)
                );
            }

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
                'trendGroups' => $trendPayloads,
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
                'trendGroups' => [],
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
                'gender' => $order->gender,
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

    protected function loadLatestStoredTrendsByGender(): array
    {
        $trends = IntelligenceTrend::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('detected_at')
            ->limit(300)
            ->get();

        if ($trends->isEmpty()) {
            return [];
        }

        $grouped = [
            'female' => [
                'segment' => 'female',
                'label' => 'Perempuan',
                'slides' => [],
                'picked_keys' => [],
            ],
            'male' => [
                'segment' => 'male',
                'label' => 'Laki-laki',
                'slides' => [],
                'picked_keys' => [],
            ],
        ];

        foreach ($trends as $trend) {
            $title = strtolower((string) $trend->title);

            if (str_contains($title, 'laki-laki')) {
                $segment = 'male';
            } elseif (str_contains($title, 'perempuan')) {
                $segment = 'female';
            } else {
                continue;
            }

            if (count($grouped[$segment]['slides']) >= 2) {
                continue;
            }

            $menuKey = $this->trendMenuIdentityKey($trend);
            if (in_array($menuKey, $grouped[$segment]['picked_keys'], true)) {
                continue;
            }

            $imageUrl = $this->resolveTrendImageUrl($trend);

            $grouped[$segment]['slides'][] = [
                'title' => $trend->title,
                'image_url' => $imageUrl,
                'caption' => $trend->caption,
                'score' => $trend->score,
                'source_timestamp' => optional($trend->source_timestamp)?->toIso8601String(),
                'expires_at' => optional($trend->expires_at)?->toIso8601String(),
                'is_active' => $trend->is_active,
            ];
            $grouped[$segment]['picked_keys'][] = $menuKey;

            if (count($grouped['female']['slides']) >= 2 && count($grouped['male']['slides']) >= 2) {
                break;
            }
        }

        $result = [];
        foreach (['female', 'male'] as $segment) {
            if ($grouped[$segment]['slides'] !== []) {
                unset($grouped[$segment]['picked_keys']);
                $result[] = $grouped[$segment];
            }
        }

        return $result;
    }

    protected function buildTrendGroupsFromPayloads(array $payloads): array
    {
        $grouped = [
            'female' => [
                'segment' => 'female',
                'label' => 'Perempuan',
                'slides' => [],
            ],
            'male' => [
                'segment' => 'male',
                'label' => 'Laki-laki',
                'slides' => [],
            ],
        ];

        foreach ($payloads as $payload) {
            $title = strtolower((string) Arr::get($payload, 'title', ''));
            $segment = null;

            if (str_contains($title, 'perempuan')) {
                $segment = 'female';
            } elseif (str_contains($title, 'laki-laki')) {
                $segment = 'male';
            }

            if ($segment === null || count($grouped[$segment]['slides']) >= 2) {
                continue;
            }

            $grouped[$segment]['slides'][] = [
                'title' => Arr::get($payload, 'title'),
                'image_url' => $this->normalizeImageUrl((string) Arr::get($payload, 'image_url')),
                'caption' => Arr::get($payload, 'caption'),
                'score' => Arr::get($payload, 'score'),
                'source_timestamp' => Arr::get($payload, 'source_timestamp'),
                'expires_at' => Arr::get($payload, 'expires_at'),
                'is_active' => (bool) Arr::get($payload, 'is_active', true),
            ];
        }

        return array_values(array_filter($grouped, fn (array $group) => $group['slides'] !== []));
    }

    protected function resolveTrendImageUrl($trend): ?string
    {
        $placeholder = (string) config('services.service_a.trend_placeholder_image');
        $stored = is_string($trend->image_url) ? trim($trend->image_url) : '';

        $candidates = [];

        if ($stored !== '' && $stored !== $placeholder) {
            $candidates[] = $stored;
        }

        $candidates[] = Arr::get($trend->payload, 'menu.image_external_url');
        $candidates[] = Arr::get($trend->payload, 'menu.image_url');

        $imagePath = Arr::get($trend->payload, 'menu.image_path');
        if (is_string($imagePath) && trim($imagePath) !== '') {
            $candidates[] = '/storage/'.ltrim($imagePath, '/');
        }

        if ($stored !== '') {
            $candidates[] = $stored;
        }

        if ($placeholder !== '') {
            $candidates[] = $placeholder;
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeImageUrl(is_string($candidate) ? $candidate : null);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    protected function normalizeImageUrl(?string $imageUrl): ?string
    {
        if ($imageUrl === null) {
            return null;
        }

        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return null;
        }

        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');
        if ($appUrl === '') {
            return $imageUrl;
        }

        if (str_starts_with($imageUrl, '/')) {
            return $appUrl.$imageUrl;
        }

        return $appUrl.'/'.ltrim($imageUrl, '/');
    }

    protected function trendMenuIdentityKey($trend): string
    {
        $slug = trim((string) Arr::get($trend->payload, 'menu.slug', ''));
        if ($slug !== '') {
            return 'slug:'.mb_strtolower($slug);
        }

        $name = trim((string) Arr::get($trend->payload, 'menu.name', ''));
        if ($name !== '') {
            return 'name:'.mb_strtolower($name);
        }

        $title = trim((string) $trend->title);

        return $title !== '' ? 'title:'.mb_strtolower($title) : 'id:'.(string) $trend->id;
    }
}
