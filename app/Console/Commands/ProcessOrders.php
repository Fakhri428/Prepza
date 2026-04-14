<?php

namespace App\Console\Commands;

use App\Exceptions\ServiceARequestException;
use App\Models\IntelligenceCategory;
use App\Models\IntelligenceMenu;
use App\Models\IntelligenceOrder;
use App\Models\IntelligenceTrend;
use App\Services\OrderAnalyzer;
use App\Services\ServiceAApiService;
use App\Services\TrendInsightService;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Throwable;

class ProcessOrders extends Command
{
    protected $signature = 'queue:process-orders {--dry-run : Simulasikan proses tanpa PATCH/POST ke Service A}';

    protected $description = 'Analyze queue orders from Service A and send intelligent priority feedback';

    public function __construct(
        protected ServiceAApiService $apiService,
        protected OrderAnalyzer $analyzer,
        protected TrendInsightService $trendInsightService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        try {
            // Ambil snapshot data dari Service A, lalu analisis seluruh order dalam satu batch.
            $useFallbackData = false;

            try {
                $categories = $this->apiService->fetchCategories();
                $menus = $this->apiService->fetchMenus();
                $orders = $this->apiService->fetchQueueOrders();
            } catch (Throwable $throwable) {
                if (! $dryRun) {
                    throw $throwable;
                }

                $this->warn('Service A tidak bisa diakses pada mode dry-run: '.$throwable->getMessage());
                $orders = $this->loadOrdersFromLocalSnapshot();
                $categories = [];
                $menus = [];
                $useFallbackData = true;

                if ($orders !== []) {
                    $this->line('[DRY-RUN] Menggunakan snapshot order lokal dari database.');
                } else {
                    $orders = $this->buildDemoOrders();
                    $this->line('[DRY-RUN] Snapshot lokal kosong, gunakan data demo internal.');
                }
            }

            if (! $useFallbackData) {
                $this->syncCatalogToLocal($categories, $menus);
                $this->syncOrdersToLocal($orders);
            }

            $menuTypeMap = $this->analyzer->buildMenuTypeMap($menus);
            $waitingCount = $this->countWaitingOrders($orders);
            $preparedUpdates = [];

            foreach ($orders as $order) {
                $orderId = (int) Arr::get($order, 'id');
                if ($orderId <= 0) {
                    $skippedCount++;
                    continue;
                }

                $analysis = $this->analyzer->analyzeOrder($order, $menuTypeMap, $waitingCount);
                $payload = $this->buildExternalUpdatePayload($order, $analysis);

                $preparedUpdates[] = [
                    'order' => $order,
                    'analysis' => $analysis,
                    'payload' => $payload,
                ];
            }

            $preparedUpdates = $this->applyPriorityQueueStrategy($preparedUpdates);

            foreach ($preparedUpdates as $entry) {
                $order = (array) Arr::get($entry, 'order', []);
                $payload = (array) Arr::get($entry, 'payload', []);
                $orderId = (int) Arr::get($order, 'id', 0);

                if (! $this->needsUpdate($order, $payload)) {
                    // Hanya kirim PATCH jika ada perubahan agar traffic tetap efisien.
                    $skippedCount++;
                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        '[DRY-RUN] Order %d | %s -> %s | note: %s',
                        $orderId,
                        (string) Arr::get($order, 'external_status', '-'),
                        (string) Arr::get($payload, 'external_status', '-'),
                        $this->shortenText((string) Arr::get($payload, 'external_note', '-'), 90)
                    ));
                    $updatedCount++;
                    continue;
                }

                try {
                    $this->apiService->patchExternalUpdate($orderId, $payload);
                    $updatedCount++;
                } catch (ServiceARequestException $exception) {
                    if ($exception->statusCode() === 404) {
                        $skippedCount++;
                        $this->warn("Order {$orderId} tidak ditemukan di Service A (404), skip.");
                        continue;
                    }

                    if ($exception->statusCode() === 422) {
                        $failedCount++;
                        $this->warn("Payload order {$orderId} tidak valid (422): {$exception->responseBody()}");
                        continue;
                    }

                    $failedCount++;
                    $this->warn("Update order {$orderId} gagal ({$exception->statusCode()}): {$exception->getMessage()}");
                } catch (Throwable $throwable) {
                    $failedCount++;
                    $this->warn("Update order {$orderId} gagal: {$throwable->getMessage()}");
                }
            }

            $trendPayloads = $this->trendInsightService->buildTrendPayloadsByGender($orders);
            foreach ($trendPayloads as $trendPayload) {
                $sentToServiceA = false;

                if ($dryRun) {
                    $this->line(sprintf(
                        '[DRY-RUN] Trend | %s | score: %s | exp: %s',
                        (string) Arr::get($trendPayload, 'title', '-'),
                        (string) Arr::get($trendPayload, 'score', '-'),
                        (string) Arr::get($trendPayload, 'expires_at', '-')
                    ));
                } else {
                    try {
                        $this->apiService->postTrendUpdate($trendPayload);
                        $sentToServiceA = true;
                    } catch (ServiceARequestException $exception) {
                        if ($exception->statusCode() === 422) {
                            $this->warn('Trend payload tidak valid (422): '.$exception->responseBody());
                        } elseif ($exception->statusCode() >= 500) {
                            $this->warn('Service A error saat kirim trend: '.$exception->getMessage());
                        } else {
                            $this->warn('Kirim trend gagal ('.$exception->statusCode().'): '.$exception->getMessage());
                        }
                    } catch (Throwable $throwable) {
                        $this->warn('Kirim trend gagal: '.$throwable->getMessage());
                    }
                }

                try {
                    $this->storeTrendLocally($trendPayload, $sentToServiceA);
                } catch (Throwable $throwable) {
                    if (! $dryRun) {
                        $this->warn('Simpan trend lokal gagal: '.$throwable->getMessage());
                    }
                }
            }

            $this->info("Selesai. Updated: {$updatedCount}, Skipped: {$skippedCount}, Failed: {$failedCount}");

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error('Proses queue gagal: '.$throwable->getMessage());

            return self::FAILURE;
        }
    }

    protected function storeTrendLocally(array $trendPayload, bool $sentToServiceA): void
    {
        IntelligenceTrend::query()->create([
            'process_run_id' => null,
            'title' => (string) Arr::get($trendPayload, 'title', ''),
            'image_url' => (string) Arr::get($trendPayload, 'image_url', ''),
            'caption' => Arr::get($trendPayload, 'caption'),
            'score' => Arr::has($trendPayload, 'score') ? (int) Arr::get($trendPayload, 'score') : null,
            'source_timestamp' => Arr::get($trendPayload, 'source_timestamp'),
            'expires_at' => Arr::get($trendPayload, 'expires_at'),
            'is_active' => (bool) Arr::get($trendPayload, 'is_active', true),
            'sent_to_service_a' => $sentToServiceA,
            'payload' => $trendPayload,
            'detected_at' => Carbon::now(),
        ]);
    }

    protected function applyPriorityQueueStrategy(array $preparedUpdates): array
    {
        if (! (bool) config('services.service_a.enforce_priority_sequence', true)) {
            return $preparedUpdates;
        }

        $maxProcessingSlots = max(1, (int) config('services.service_a.max_processing_slots', 1));
        $eligible = [];

        foreach ($preparedUpdates as $index => $entry) {
            $order = (array) Arr::get($entry, 'order', []);
            $orderStatus = strtolower((string) Arr::get($order, 'status', ''));
            $preparedStatus = strtolower((string) Arr::get($entry, 'payload.external_status', ''));

            if (in_array($orderStatus, ['done', 'cancelled'], true) || in_array($preparedStatus, ['done', 'cancelled'], true)) {
                continue;
            }

            $priority = strtolower((string) Arr::get($entry, 'analysis.priority', 'low'));
            $queueNumber = (int) Arr::get($order, 'queue.queue_number', PHP_INT_MAX);

            $eligible[] = [
                'index' => $index,
                'priority_rank' => $this->priorityRank($priority),
                'queue_number' => $queueNumber,
                'order_id' => (int) Arr::get($order, 'id', PHP_INT_MAX),
            ];
        }

        usort($eligible, function (array $a, array $b): int {
            return [$a['priority_rank'], $a['queue_number'], $a['order_id']]
                <=> [$b['priority_rank'], $b['queue_number'], $b['order_id']];
        });

        foreach ($eligible as $position => $row) {
            $index = (int) $row['index'];
            $targetStatus = $position < $maxProcessingSlots ? 'processing' : 'waiting';

            $preparedUpdates[$index]['payload']['external_status'] = $targetStatus;
            $preparedUpdates[$index]['payload']['queue_status'] = $targetStatus;
        }

        return $preparedUpdates;
    }

    protected function priorityRank(string $priority): int
    {
        return match ($priority) {
            'high' => 1,
            'medium' => 2,
            default => 3,
        };
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

    protected function needsUpdate(array $order, array $payload): bool
    {
        $currentStatus = (string) Arr::get($order, 'external_status', '');
        $currentNote = trim((string) Arr::get($order, 'external_note', ''));
        $newStatus = (string) Arr::get($payload, 'external_status', '');
        $newNote = trim((string) Arr::get($payload, 'external_note', ''));

        return $currentStatus !== $newStatus || $currentNote !== $newNote;
    }

    protected function buildExternalUpdatePayload(array $order, array $analysis): array
    {
        $orderStatus = strtolower((string) Arr::get($order, 'status', ''));

        if ($this->shouldAutoCompleteOrder($order)) {
            $payload = [
                'external_status' => 'done',
                'external_note' => 'Pesanan otomatis diselesaikan oleh Service B setelah melewati batas waktu proses.',
            ];

            if ((bool) config('services.service_a.send_queue_status', false)) {
                $payload['queue_status'] = 'done';
            }

            return $payload;
        }

        $externalStatus = $orderStatus === 'cancelled'
            ? 'cancelled'
            : ($orderStatus === 'done'
                ? 'done'
                : (string) Arr::get($analysis, 'external_status', 'waiting'));

        $externalNote = $orderStatus === 'cancelled'
            ? 'Pesanan ditandai dibatalkan pada sinkronisasi Service B.'
            : ($orderStatus === 'done'
                ? 'Pesanan ditandai selesai pada sinkronisasi Service B.'
                : (string) Arr::get($analysis, 'external_note', ''));

        $noteMaxLength = max(1, (int) config('services.service_a.note_max_length', 500));
        $externalNote = trim(mb_substr($externalNote, 0, $noteMaxLength));

        $payload = [
            'external_status' => $externalStatus,
            'external_note' => $externalNote,
        ];

        if ((bool) config('services.service_a.send_queue_status', false)) {
            $payload['queue_status'] = $orderStatus === 'cancelled' ? 'cancelled' : $externalStatus;
        }

        return $payload;
    }

    protected function shouldAutoCompleteOrder(array $order): bool
    {
        if (! (bool) config('services.service_a.auto_done_enabled', true)) {
            return false;
        }

        $orderStatus = strtolower((string) Arr::get($order, 'status', ''));
        $externalStatus = strtolower((string) Arr::get($order, 'external_status', ''));

        if (! in_array($orderStatus, ['waiting', 'processing'], true)) {
            return false;
        }

        if ($externalStatus !== 'processing') {
            return false;
        }

        $thresholdMinutes = max(1, (int) config('services.service_a.auto_done_minutes', 20));
        $reference = Arr::get($order, 'external_updated_at') ?: Arr::get($order, 'created_at');

        if (! is_string($reference) || trim($reference) === '') {
            return false;
        }

        try {
            return Carbon::parse($reference)->lte(Carbon::now()->subMinutes($thresholdMinutes));
        } catch (Throwable) {
            return false;
        }
    }

    protected function syncOrdersToLocal(array $orders): void
    {
        $syncedAt = Carbon::now();

        foreach ($orders as $order) {
            $serviceAOrderId = (int) Arr::get($order, 'id');
            if ($serviceAOrderId <= 0) {
                continue;
            }

            $localOrder = IntelligenceOrder::updateOrCreate(
                ['service_a_order_id' => $serviceAOrderId],
                [
                    'order_code' => Arr::get($order, 'order_code'),
                    'customer_name' => Arr::get($order, 'customer_name'),
                    'gender' => Arr::get($order, 'gender'),
                    'status' => Arr::get($order, 'status'),
                    'external_status' => Arr::get($order, 'external_status'),
                    'external_note' => Arr::get($order, 'external_note'),
                    'external_updated_at' => Arr::get($order, 'external_updated_at'),
                    'total_amount' => Arr::get($order, 'total_amount'),
                    'queue_number' => Arr::get($order, 'queue.queue_number'),
                    'queue_status' => Arr::get($order, 'queue.status'),
                    'service_a_created_at' => Arr::get($order, 'created_at'),
                    'last_synced_at' => $syncedAt,
                ]
            );

            $localOrder->items()->delete();

            foreach ((array) Arr::get($order, 'items', []) as $item) {
                $localOrder->items()->create([
                    'service_a_item_id' => Arr::get($item, 'id'),
                    'item_name' => Arr::get($item, 'item_name'),
                    'note' => Arr::get($item, 'note'),
                    'qty' => max(1, (int) Arr::get($item, 'qty', 1)),
                    'subtotal' => Arr::get($item, 'subtotal'),
                    'last_synced_at' => $syncedAt,
                ]);
            }
        }
    }

    protected function syncCatalogToLocal(array $categories, array $menus): void
    {
        $syncedAt = Carbon::now();
        $localCategoryMap = [];

        foreach ($categories as $category) {
            $serviceACategoryId = (int) Arr::get($category, 'id');
            if ($serviceACategoryId <= 0) {
                continue;
            }

            $localCategory = IntelligenceCategory::query()->updateOrCreate(
                ['service_a_category_id' => $serviceACategoryId],
                [
                    'name' => (string) Arr::get($category, 'name', ''),
                    'slug' => Arr::get($category, 'slug'),
                    'description' => Arr::get($category, 'description'),
                    'is_active' => (bool) Arr::get($category, 'is_active', true),
                    'menu_count' => max(0, (int) Arr::get($category, 'menu_count', 0)),
                    'service_a_created_at' => Arr::get($category, 'created_at'),
                    'last_synced_at' => $syncedAt,
                ]
            );

            $localCategoryMap[$serviceACategoryId] = (int) $localCategory->id;
        }

        foreach ($menus as $menu) {
            $serviceAMenuId = (int) Arr::get($menu, 'id');
            if ($serviceAMenuId <= 0) {
                continue;
            }

            $serviceACategoryId = (int) (Arr::get($menu, 'category_id') ?: Arr::get($menu, 'category.id', 0));
            $localCategoryId = $serviceACategoryId > 0 ? ($localCategoryMap[$serviceACategoryId] ?? null) : null;

            if ($localCategoryId === null && $serviceACategoryId > 0) {
                $categoryName = (string) Arr::get($menu, 'category.name', '');
                $localCategory = IntelligenceCategory::query()->updateOrCreate(
                    ['service_a_category_id' => $serviceACategoryId],
                    [
                        'name' => $categoryName !== '' ? $categoryName : 'Unknown',
                        'slug' => Arr::get($menu, 'category.slug'),
                        'description' => null,
                        'is_active' => true,
                        'menu_count' => 0,
                        'service_a_created_at' => null,
                        'last_synced_at' => $syncedAt,
                    ]
                );

                $localCategoryId = (int) $localCategory->id;
                $localCategoryMap[$serviceACategoryId] = $localCategoryId;
            }

            $localMenu = IntelligenceMenu::query()->updateOrCreate(
                ['service_a_menu_id' => $serviceAMenuId],
                [
                    'intelligence_category_id' => $localCategoryId,
                    'service_a_category_id' => $serviceACategoryId > 0 ? $serviceACategoryId : null,
                    'name' => (string) Arr::get($menu, 'name', ''),
                    'slug' => Arr::get($menu, 'slug'),
                    'description' => Arr::get($menu, 'description'),
                    'image_path' => Arr::get($menu, 'image_path'),
                    'image_external_url' => Arr::get($menu, 'image_external_url'),
                    'image_url' => Arr::get($menu, 'image_url'),
                    'price' => Arr::get($menu, 'price'),
                    'is_active' => (bool) Arr::get($menu, 'is_active', true),
                    'last_synced_at' => $syncedAt,
                ]
            );

            $localMenu->aliases()->delete();

            foreach ((array) Arr::get($menu, 'aliases', []) as $alias) {
                $aliasValue = trim((string) Arr::get($alias, 'alias', ''));
                if ($aliasValue === '') {
                    continue;
                }

                $localMenu->aliases()->create([
                    'service_a_alias_id' => Arr::get($alias, 'id'),
                    'alias' => $aliasValue,
                    'normalized_alias' => Arr::get($alias, 'normalized_alias'),
                    'last_synced_at' => $syncedAt,
                ]);
            }
        }
    }

    protected function loadOrdersFromLocalSnapshot(int $limit = 25): array
    {
        return IntelligenceOrder::query()
            ->with('items')
            ->orderByDesc('last_synced_at')
            ->limit($limit)
            ->get()
            ->map(function (IntelligenceOrder $order): array {
                return [
                    'id' => (int) $order->service_a_order_id,
                    'order_code' => $order->order_code,
                    'customer_name' => $order->customer_name,
                    'gender' => $order->gender,
                    'status' => $order->status,
                    'external_status' => $order->external_status,
                    'external_note' => $order->external_note,
                    'created_at' => optional($order->service_a_created_at)?->toIso8601String(),
                    'items' => $order->items->map(function ($item): array {
                        return [
                            'id' => $item->service_a_item_id,
                            'item_name' => $item->item_name,
                            'note' => $item->note,
                            'qty' => (int) $item->qty,
                            'subtotal' => $item->subtotal,
                        ];
                    })->all(),
                    'queue' => [
                        'queue_number' => $order->queue_number,
                        'status' => $order->queue_status,
                    ],
                ];
            })
            ->all();
    }

    protected function buildDemoOrders(): array
    {
        return [
            [
                'id' => 9001,
                'order_code' => 'DEMO-9001',
                'customer_name' => 'Demo Andi',
                'gender' => 'male',
                'status' => 'waiting',
                'external_status' => 'waiting',
                'external_note' => 'menunggu diproses',
                'items' => [
                    ['id' => 1, 'item_name' => 'Ayam Geprek', 'note' => null, 'qty' => 2, 'subtotal' => null],
                    ['id' => 2, 'item_name' => 'Es Teh', 'note' => null, 'qty' => 1, 'subtotal' => null],
                ],
                'queue' => ['queue_number' => 1, 'status' => 'waiting'],
            ],
            [
                'id' => 9002,
                'order_code' => 'DEMO-9002',
                'customer_name' => 'Demo Sinta',
                'gender' => 'female',
                'status' => 'waiting',
                'external_status' => 'waiting',
                'external_note' => 'menunggu diproses',
                'items' => [
                    ['id' => 3, 'item_name' => 'Salad Buah', 'note' => null, 'qty' => 2, 'subtotal' => null],
                    ['id' => 4, 'item_name' => 'Es Teh', 'note' => null, 'qty' => 1, 'subtotal' => null],
                ],
                'queue' => ['queue_number' => 2, 'status' => 'waiting'],
            ],
        ];
    }

    protected function shortenText(string $text, int $maxLength = 90): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if (mb_strlen($clean) <= $maxLength) {
            return $clean;
        }

        return rtrim(mb_substr($clean, 0, $maxLength - 1)).'…';
    }
}
