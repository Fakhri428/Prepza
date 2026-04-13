<?php

namespace Tests\Feature;

use App\Models\IntelligenceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fetches_analyzes_and_updates_orders(): void
    {
        config([
            'services.service_a.base_url' => 'http://service-a.test',
            'services.service_a.fetch_statuses' => 'queued,waiting,processing',
            'services.service_a.busy_threshold' => 5,
            'services.service_a.overload_threshold' => 10,
            'services.service_a.trend_min_repeat' => 1,
            'services.service_a.trend_placeholder_image' => 'https://example.com/trend.jpg',
            'services.service_a.trend_expire_minutes' => 30,
        ]);

        Http::fake([
            'http://service-a.test/api/menus' => Http::response([
                'data' => [
                    ['name' => 'Es Teh', 'category' => ['name' => 'Minuman']],
                ],
            ], 200),
            'http://service-a.test/api/queue/orders*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
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
        ]);

        $localOrder = IntelligenceOrder::where('service_a_order_id', 1)->first();
        $this->assertNotNull($localOrder);
        $this->assertSame(1, $localOrder->items()->count());
    }
}
