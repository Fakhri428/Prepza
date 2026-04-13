<?php

namespace App\Console\Commands;

use App\Models\IntelligenceOrder;
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
            $menus = $this->apiService->fetchMenus();
            $orders = $this->apiService->fetchQueueOrders();
            $this->syncOrdersToLocal($orders);
            $menuTypeMap = $this->analyzer->buildMenuTypeMap($menus);
            $waitingCount = $this->countWaitingOrders($orders);

            foreach ($orders as $order) {
                $orderId = (int) Arr::get($order, 'id');
                if ($orderId <= 0) {
                    $skippedCount++;
                    continue;
                }

                $analysis = $this->analyzer->analyzeOrder($order, $menuTypeMap, $waitingCount);
                $payload = [
                    'external_status' => $analysis['external_status'],
                    'external_note' => $analysis['external_note'],
                ];

                if (! $this->needsUpdate($order, $payload)) {
                    // Hanya kirim PATCH jika ada perubahan agar traffic tetap efisien.
                    $skippedCount++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("[DRY-RUN] Order {$orderId} -> ".json_encode($payload, JSON_UNESCAPED_UNICODE));
                    $updatedCount++;
                    continue;
                }

                try {
                    $this->apiService->patchExternalUpdate($orderId, $payload);
                    $updatedCount++;
                } catch (Throwable $throwable) {
                    $failedCount++;
                    $this->warn("Update order {$orderId} gagal: {$throwable->getMessage()}");
                }
            }

            $trendPayload = $this->trendInsightService->buildTrendPayload($orders);
            if ($trendPayload !== null) {
                if ($dryRun) {
                    $this->line('[DRY-RUN] Trend payload: '.json_encode($trendPayload, JSON_UNESCAPED_UNICODE));
                } else {
                    try {
                        $this->apiService->postTrendUpdate($trendPayload);
                    } catch (Throwable $throwable) {
                        $this->warn('Kirim trend gagal: '.$throwable->getMessage());
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
}
