<?php

namespace Tests\Feature;

use App\Models\IntelligenceOrder;
use App\Models\User;
use Database\Seeders\IntelligenceDashboardDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntelligenceDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_intelligence_dashboard_reads_local_intelligence_tables(): void
    {
        $this->seed(IntelligenceDashboardDummySeeder::class);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('intelligence.dashboard'));

        $response->assertOk();
        $response->assertSee('Intelligence Dashboard');
        $response->assertSee('ORD-DMY-7001');
        $response->assertSee('Budi');
        $response->assertSee('Ayam Geprek Sedang Naik (laki-laki)');
    }

    public function test_dashboard_can_mark_order_processing_and_done(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = IntelligenceOrder::query()->create([
            'service_a_order_id' => 7001,
            'order_code' => 'ORD-7001',
            'customer_name' => 'Budi',
            'gender' => 'male',
            'status' => 'waiting',
            'external_status' => 'waiting',
            'external_note' => 'menunggu',
            'external_updated_at' => now(),
            'total_amount' => 10000,
            'queue_number' => 1,
            'queue_status' => 'waiting',
            'service_a_created_at' => now(),
            'last_synced_at' => now(),
        ]);

        config([
            'services.service_a.base_url' => 'http://service-a.test',
        ]);

        Http::fake([
            'http://service-a.test/api/queue/orders/7001/external-update' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->actingAs($user)
            ->post(route('intelligence.orders.update-status', $order), [
                'target_status' => 'processing',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('intelligence_orders', [
            'service_a_order_id' => 7001,
            'external_status' => 'processing',
            'queue_status' => 'processing',
            'status' => 'waiting',
        ]);

        $this->actingAs($user)
            ->post(route('intelligence.orders.update-status', $order), [
                'target_status' => 'done',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('intelligence_orders', [
            'service_a_order_id' => 7001,
            'external_status' => 'done',
            'queue_status' => 'done',
            'status' => 'done',
        ]);
    }
}
