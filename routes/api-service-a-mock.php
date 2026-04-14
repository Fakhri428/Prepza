<?php

use App\Models\IntelligenceOrder;
use App\Models\IntelligenceTrend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/categories', function (): JsonResponse {
    return response()->json([
        'data' => [
            [
                'id' => 1,
                'name' => 'Makanan',
                'slug' => 'makanan',
                'description' => 'Menu makanan utama',
                'is_active' => true,
                'menu_count' => 12,
                'created_at' => now()->toIso8601String(),
            ],
            [
                'id' => 2,
                'name' => 'Minuman',
                'slug' => 'minuman',
                'description' => 'Menu minuman',
                'is_active' => true,
                'menu_count' => 8,
                'created_at' => now()->toIso8601String(),
            ],
        ],
    ]);
});

Route::get('/menus', function (): JsonResponse {
    return response()->json([
        'data' => [
            [
                'id' => 10,
                'name' => 'Nasi Goreng',
                'slug' => 'nasi-goreng',
                'description' => 'Nasi goreng spesial',
                'image_path' => 'menus/nasi-goreng.jpg',
                'image_external_url' => null,
                'image_url' => '/storage/menus/nasi-goreng.jpg',
                'price' => '20000.00',
                'is_active' => true,
                'category_id' => 1,
                'category' => [
                    'id' => 1,
                    'name' => 'Makanan',
                    'slug' => 'makanan',
                ],
                'aliases' => [
                    [
                        'id' => 44,
                        'alias' => 'nasgor',
                        'normalized_alias' => 'nasgor',
                    ],
                ],
            ],
        ],
    ]);
});

Route::get('/queue/orders', function (Request $request): JsonResponse {
    $requestedStatuses = collect(explode(',', (string) $request->query('status', 'queued,waiting,processing')))
        ->map(fn (string $status) => strtolower(trim($status)))
        ->filter()
        ->values();

    $orders = IntelligenceOrder::query()
        ->with('items')
        ->when($requestedStatuses->isNotEmpty(), fn ($query) => $query->whereIn('status', $requestedStatuses->all()))
        ->orderBy('queue_number')
        ->get()
        ->map(function (IntelligenceOrder $order): array {
            return [
                'id' => $order->service_a_order_id,
                'order_code' => $order->order_code,
                'customer_name' => $order->customer_name,
                'gender' => $order->gender,
                'status' => $order->status,
                'external_status' => $order->external_status,
                'external_note' => $order->external_note,
                'external_updated_at' => optional($order->external_updated_at)?->toIso8601String(),
                'total_amount' => (string) $order->total_amount,
                'created_at' => optional($order->service_a_created_at)?->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->service_a_item_id,
                    'order_id' => $order->service_a_order_id,
                    'item_name' => $item->item_name,
                    'note' => $item->note,
                    'qty' => (int) $item->qty,
                    'subtotal' => (string) $item->subtotal,
                ])->values()->all(),
                'queue' => [
                    'queue_number' => $order->queue_number,
                    'order_id' => $order->service_a_order_id,
                    'status' => $order->queue_status,
                ],
            ];
        })
        ->values();

    return response()->json(['data' => $orders]);
});

Route::patch('/queue/orders/{order}/external-update', function (Request $request, int $order): JsonResponse {
    $validator = Validator::make($request->all(), [
        'external_status' => ['nullable', 'in:waiting,processing,done'],
        'external_note' => ['nullable', 'string', 'max:500'],
        'queue_status' => ['nullable', 'in:waiting,processing,done,cancelled'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $target = IntelligenceOrder::query()->where('service_a_order_id', $order)->first();
    if (! $target) {
        return response()->json([
            'message' => "No query results for model [App\\Models\\Order] {$order}",
        ], 404);
    }

    $externalStatus = $request->input('external_status', $target->external_status);
    $queueStatus = $request->input('queue_status');
    if ($queueStatus === null && $externalStatus !== null) {
        $queueStatus = $externalStatus;
    }

    $target->external_status = $externalStatus;
    $target->external_note = $request->input('external_note', $target->external_note);
    $target->external_updated_at = now();
    if ($queueStatus !== null) {
        $target->queue_status = $queueStatus === 'cancelled' ? 'done' : $queueStatus;
    }
    if ($externalStatus === 'done') {
        $target->status = 'done';
    }
    $target->save();

    return response()->json([
        'status' => 'ok',
        'message' => 'Simulasi input eksternal berhasil diproses.',
        'order' => [
            'id' => $target->service_a_order_id,
            'order_code' => $target->order_code,
            'status' => $target->status,
            'external_status' => $target->external_status,
            'external_note' => $target->external_note,
            'external_updated_at' => optional($target->external_updated_at)?->toIso8601String(),
            'queue' => [
                'queue_number' => $target->queue_number,
                'order_id' => $target->service_a_order_id,
                'status' => $target->queue_status,
            ],
        ],
        'updated_by' => null,
    ]);
});

Route::post('/queue/trends/update', function (Request $request): JsonResponse {
    $validator = Validator::make($request->all(), [
        'title' => ['required', 'string', 'max:120'],
        'image_url' => ['required', 'url', 'max:2048'],
        'caption' => ['nullable', 'string', 'max:300'],
        'score' => ['nullable', 'integer', 'between:0,100'],
        'source_timestamp' => ['nullable', 'date'],
        'expires_at' => ['nullable', 'date', 'after:now'],
        'is_active' => ['nullable', 'boolean'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $trend = IntelligenceTrend::query()->create([
        'process_run_id' => null,
        'title' => (string) $request->input('title'),
        'image_url' => (string) $request->input('image_url'),
        'caption' => $request->input('caption'),
        'score' => $request->input('score'),
        'source_timestamp' => $request->input('source_timestamp') ? Carbon::parse((string) $request->input('source_timestamp')) : null,
        'expires_at' => $request->input('expires_at') ? Carbon::parse((string) $request->input('expires_at')) : null,
        'is_active' => (bool) $request->input('is_active', true),
        'sent_to_service_a' => true,
        'payload' => $request->all(),
        'detected_at' => now(),
    ]);

    return response()->json([
        'status' => 'ok',
        'message' => 'Tren makanan berhasil diperbarui.',
        'data' => [
            'id' => $trend->id,
            'title' => $trend->title,
            'image_url' => $trend->image_url,
            'caption' => $trend->caption,
            'score' => $trend->score,
            'source_timestamp' => optional($trend->source_timestamp)?->toIso8601String(),
            'expires_at' => optional($trend->expires_at)?->toIso8601String(),
            'is_active' => $trend->is_active,
        ],
    ]);
});
