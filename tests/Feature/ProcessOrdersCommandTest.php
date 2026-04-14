<?php

namespace Tests\Feature;

use App\Models\IntelligenceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_roundtrip_from_service_a_to_service_b_and_back_to_service_a(): void
    {
        config([
            'services.service_a.base_url' => 'http://service-a.test',
            'services.service_a.fetch_statuses' => 'queued,waiting,processing',
            'services.service_a.busy_threshold' => 5,
            'services.service_a.overload_threshold' => 10,
            'services.service_a.trend_min_repeat' => 1,
            'services.service_a.trend_min_repeat_gender' => 1,
            'services.service_a.trend_placeholder_image' => 'https://example.com/trend.jpg',
            'services.service_a.trend_expire_minutes' => 30,
        ]);

        Http::fake([
            'http://service-a.test/api/categories' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'Makanan Berat',
                        'slug' => 'makanan-berat',
                        'description' => 'Kategori makanan berat',
                        'is_active' => true,
                        'menu_count' => 1,
                        'created_at' => now()->subDay()->toIso8601String(),
                    ],
                    [
                        'id' => 2,
                        'name' => 'Makanan Ringan',
                        'slug' => 'makanan-ringan',
                        'description' => 'Kategori makanan ringan',
                        'is_active' => true,
                        'menu_count' => 1,
                        'created_at' => now()->subDay()->toIso8601String(),
                    ],
                ],
            ], 200),
            'http://service-a.test/api/menus' => Http::response([
                'data' => [
                    [
                        'id' => 10,
                        'name' => 'Ayam Geprek',
                        'slug' => 'ayam-geprek',
                        'price' => '22000.00',
                        'is_active' => true,
                        'category_id' => 1,
                        'category' => ['id' => 1, 'name' => 'Makanan Berat', 'slug' => 'makanan-berat'],
                        'aliases' => [
                            ['id' => 501, 'alias' => 'geprek', 'normalized_alias' => 'geprek'],
                        ],
                    ],
                    [
                        'id' => 20,
                        'name' => 'Salad Buah',
                        'slug' => 'salad-buah',
                        'price' => '18000.00',
                        'is_active' => true,
                        'category_id' => 2,
                        'category' => ['id' => 2, 'name' => 'Makanan Ringan', 'slug' => 'makanan-ringan'],
                        'aliases' => [
                            ['id' => 502, 'alias' => 'salbu', 'normalized_alias' => 'salbu'],
                        ],
                    ],
                ],
            ], 200),
            'http://service-a.test/api/queue/orders*' => Http::response([
                'data' => [
                    [
                        'id' => 101,
                        'order_code' => 'ORD-101',
                        'customer_name' => 'Andi',
                        'gender' => 'male',
                        'status' => 'waiting',
                        'external_status' => 'waiting',
                        'external_note' => 'menunggu',
                        'items' => [
                            ['item_name' => 'Ayam Geprek', 'qty' => 2],
                        ],
                    ],
                    [
                        'id' => 102,
                        'order_code' => 'ORD-102',
                        'customer_name' => 'Sinta',
                        'gender' => 'female',
                        'status' => 'waiting',
                        'external_status' => 'waiting',
                        'external_note' => 'menunggu',
                        'items' => [
                            ['item_name' => 'Salad Buah', 'qty' => 3],
                        ],
                    ],
                ],
            ], 200),
            'http://service-a.test/api/queue/orders/101/external-update' => Http::response([
                'status' => 'ok',
            ], 200),
            'http://service-a.test/api/queue/orders/102/external-update' => Http::response([
                'status' => 'ok',
            ], 200),
            'http://service-a.test/api/queue/trends/update' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->artisan('queue:process-orders')
            ->expectsOutputToContain('Selesai.')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/orders/101/external-update'
                && in_array($request['external_status'], ['waiting', 'processing'], true);
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/orders/102/external-update'
                && in_array($request['external_status'], ['waiting', 'processing'], true);
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/trends/update'
                && str_contains((string) $request['title'], '(laki-laki)');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/trends/update'
                && str_contains((string) $request['title'], '(laki-laki)')
                && (string) data_get($request->data(), 'menu.name') === 'Ayam Geprek'
                && (string) data_get($request->data(), 'menu.slug') === 'ayam-geprek'
                && data_get($request->data(), 'menu.description') === null
                && (string) data_get($request->data(), 'menu.price') === '22000.00'
                && (bool) data_get($request->data(), 'menu.is_active') === true;
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/trends/update'
                && str_contains((string) $request['title'], '(perempuan)');
        });

        $this->assertDatabaseHas('intelligence_orders', [
            'service_a_order_id' => 101,
            'customer_name' => 'Andi',
            'gender' => 'male',
        ]);

        $this->assertDatabaseHas('intelligence_orders', [
            'service_a_order_id' => 102,
            'customer_name' => 'Sinta',
            'gender' => 'female',
        ]);

        $this->assertDatabaseHas('intelligence_trends', [
            'title' => 'Ayam Geprek Sedang Naik (laki-laki)',
            'sent_to_service_a' => 1,
        ]);

        $this->assertDatabaseHas('intelligence_trends', [
            'title' => 'Salad Buah Sedang Naik (perempuan)',
            'sent_to_service_a' => 1,
        ]);

        $this->assertDatabaseHas('intelligence_categories', [
            'service_a_category_id' => 1,
            'name' => 'Makanan Berat',
        ]);

        $this->assertDatabaseHas('intelligence_menus', [
            'service_a_menu_id' => 10,
            'name' => 'Ayam Geprek',
            'service_a_category_id' => 1,
        ]);

        $this->assertDatabaseHas('intelligence_menu_aliases', [
            'service_a_alias_id' => 501,
            'alias' => 'geprek',
            'normalized_alias' => 'geprek',
        ]);
    }

    public function test_command_fetches_analyzes_and_updates_orders(): void
    {
        config([
            'services.service_a.base_url' => 'http://service-a.test',
            'services.service_a.fetch_statuses' => 'queued,waiting,processing',
            'services.service_a.busy_threshold' => 5,
            'services.service_a.overload_threshold' => 10,
            'services.service_a.trend_min_repeat' => 1,
            'services.service_a.trend_min_repeat_gender' => 1,
            'services.service_a.trend_placeholder_image' => 'https://example.com/trend.jpg',
            'services.service_a.trend_expire_minutes' => 30,
        ]);

        Http::fake([
            'http://service-a.test/api/categories' => Http::response([
                'data' => [
                    [
                        'id' => 3,
                        'name' => 'Minuman',
                        'slug' => 'minuman',
                        'description' => 'Kategori minuman',
                        'is_active' => true,
                        'menu_count' => 1,
                        'created_at' => now()->subDay()->toIso8601String(),
                    ],
                ],
            ], 200),
            'http://service-a.test/api/menus' => Http::response([
                'data' => [
                    [
                        'id' => 99,
                        'name' => 'Es Teh',
                        'slug' => 'es-teh',
                        'price' => '5000.00',
                        'is_active' => true,
                        'category_id' => 3,
                        'category' => ['id' => 3, 'name' => 'Minuman', 'slug' => 'minuman'],
                        'aliases' => [
                            ['id' => 700, 'alias' => 'esteh', 'normalized_alias' => 'esteh'],
                        ],
                    ],
                ],
            ], 200),
            'http://service-a.test/api/queue/orders*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'gender' => 'male',
                        'status' => 'waiting',
                        'external_status' => 'waiting',
                        'external_note' => 'lama',
                        'items' => [
                            ['item_name' => 'Es Teh', 'qty' => 1],
                        ],
                    ],
                ],
            ], 200),
            'http://service-a.test/api/queue/orders/1/external-update' => Http::response([
                'status' => 'ok',
            ], 200),
            'http://service-a.test/api/queue/trends/update' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->artisan('queue:process-orders')
            ->expectsOutputToContain('Selesai.')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/orders/1/external-update'
                && $request['external_status'] === 'processing';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/trends/update'
                && $request['image_url'] === 'https://example.com/trend.jpg';
        });

        $this->assertDatabaseHas('intelligence_orders', [
            'service_a_order_id' => 1,
            'order_code' => null,
            'customer_name' => null,
            'gender' => 'male',
        ]);

        $localOrder = IntelligenceOrder::where('service_a_order_id', 1)->first();
        $this->assertNotNull($localOrder);
        $this->assertSame(1, $localOrder->items()->count());

        $this->assertDatabaseHas('intelligence_trends', [
            'title' => 'Es Teh Sedang Naik (laki-laki)',
            'sent_to_service_a' => 1,
        ]);

        $this->assertDatabaseHas('intelligence_categories', [
            'service_a_category_id' => 3,
            'name' => 'Minuman',
        ]);

        $this->assertDatabaseHas('intelligence_menus', [
            'service_a_menu_id' => 99,
            'name' => 'Es Teh',
            'service_a_category_id' => 3,
        ]);

        $this->assertDatabaseHas('intelligence_menu_aliases', [
            'service_a_alias_id' => 700,
            'alias' => 'esteh',
        ]);
    }

    public function test_dry_run_uses_fallback_data_when_service_a_unavailable(): void
    {
        config([
            'services.service_a.base_url' => 'http://service-a.test',
            'services.service_a.trend_min_repeat' => 1,
            'services.service_a.trend_min_repeat_gender' => 1,
        ]);

        Http::fake([
            'http://service-a.test/api/menus' => Http::response(['message' => 'timeout'], 504),
            'http://service-a.test/api/queue/orders*' => Http::response(['message' => 'timeout'], 504),
            'http://service-a.test/api/categories' => Http::response(['message' => 'timeout'], 504),
        ]);

        $this->artisan('queue:process-orders --dry-run')
            ->expectsOutputToContain('Service A tidak bisa diakses pada mode dry-run')
            ->expectsOutputToContain('gunakan data demo internal')
            ->expectsOutputToContain('Selesai.')
            ->assertSuccessful();

        $this->assertDatabaseHas('intelligence_trends', [
            'title' => 'Ayam Geprek Sedang Naik (laki-laki)',
            'sent_to_service_a' => 0,
        ]);

        $this->assertDatabaseHas('intelligence_trends', [
            'title' => 'Salad Buah Sedang Naik (perempuan)',
            'sent_to_service_a' => 0,
        ]);
    }

    public function test_auto_done_marks_long_processing_order_as_done(): void
    {
        config([
            'services.service_a.base_url' => 'http://service-a.test',
            'services.service_a.fetch_statuses' => 'queued,waiting,processing',
            'services.service_a.auto_done_enabled' => true,
            'services.service_a.auto_done_minutes' => 10,
            'services.service_a.enforce_priority_sequence' => false,
            'services.service_a.trend_min_repeat' => 99,
            'services.service_a.trend_min_repeat_gender' => 99,
        ]);

        Http::fake([
            'http://service-a.test/api/categories' => Http::response([
                'data' => [
                    [
                        'id' => 10,
                        'name' => 'Makanan',
                        'slug' => 'makanan',
                        'description' => 'Kategori makanan',
                        'is_active' => true,
                        'menu_count' => 0,
                        'created_at' => now()->subDay()->toIso8601String(),
                    ],
                ],
            ], 200),
            'http://service-a.test/api/menus' => Http::response([
                'data' => [],
            ], 200),
            'http://service-a.test/api/queue/orders*' => Http::response([
                'data' => [
                    [
                        'id' => 201,
                        'gender' => 'male',
                        'status' => 'processing',
                        'external_status' => 'processing',
                        'external_note' => 'sedang diproses',
                        'external_updated_at' => now()->subMinutes(20)->toIso8601String(),
                        'created_at' => now()->subMinutes(30)->toIso8601String(),
                        'items' => [
                            ['item_name' => 'Nasi Goreng', 'qty' => 1],
                        ],
                    ],
                ],
            ], 200),
            'http://service-a.test/api/queue/orders/201/external-update' => Http::response([
                'status' => 'ok',
            ], 200),
            'http://service-a.test/api/queue/trends/update' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->artisan('queue:process-orders')
            ->expectsOutputToContain('Selesai.')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://service-a.test/api/queue/orders/201/external-update'
                && $request['external_status'] === 'done';
        });
    }

    public function test_fetch_queue_orders_always_includes_cancelled_status(): void
    {
        config([
            'services.service_a.base_url' => 'http://service-a.test',
            'services.service_a.fetch_statuses' => 'queued,waiting,processing',
            'services.service_a.trend_min_repeat' => 99,
            'services.service_a.trend_min_repeat_gender' => 99,
        ]);

        Http::fake([
            'http://service-a.test/api/categories' => Http::response(['data' => []], 200),
            'http://service-a.test/api/menus' => Http::response(['data' => []], 200),
            'http://service-a.test/api/queue/orders*' => Http::response(['data' => []], 200),
            'http://service-a.test/api/queue/trends/update' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->artisan('queue:process-orders')->assertSuccessful();

        Http::assertSent(function ($request) {
            if (! str_starts_with($request->url(), 'http://service-a.test/api/queue/orders')) {
                return false;
            }

            $status = strtolower((string) data_get($request->data(), 'status', ''));

            return str_contains($status, 'cancelled');
        });
    }
}
