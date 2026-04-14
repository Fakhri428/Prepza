<?php

namespace Tests\Feature;

use App\Models\IntelligenceOrder;
use App\Models\IntelligenceTrend;
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

    public function test_dashboard_trend_groups_deduplicate_same_menu_and_keep_male_group_visible(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        IntelligenceTrend::query()->create([
            'process_run_id' => null,
            'title' => 'Ayam Bakar Sedang Naik (perempuan)',
            'image_url' => 'https://example.com/a.jpg',
            'caption' => 'A',
            'score' => 90,
            'source_timestamp' => now(),
            'expires_at' => now()->addHour(),
            'is_active' => true,
            'sent_to_service_a' => true,
            'payload' => ['menu' => ['slug' => 'ayam-bakar', 'name' => 'Ayam Bakar']],
            'detected_at' => now()->subMinute(),
        ]);

        IntelligenceTrend::query()->create([
            'process_run_id' => null,
            'title' => 'Ayam Bakar Sedang Naik (perempuan)',
            'image_url' => 'https://example.com/a2.jpg',
            'caption' => 'A2',
            'score' => 89,
            'source_timestamp' => now(),
            'expires_at' => now()->addHour(),
            'is_active' => true,
            'sent_to_service_a' => true,
            'payload' => ['menu' => ['slug' => 'ayam-bakar', 'name' => 'Ayam Bakar']],
            'detected_at' => now()->subSeconds(40),
        ]);

        IntelligenceTrend::query()->create([
            'process_run_id' => null,
            'title' => 'Jus Jeruk Sedang Naik (perempuan)',
            'image_url' => 'https://example.com/j.jpg',
            'caption' => 'J',
            'score' => 80,
            'source_timestamp' => now(),
            'expires_at' => now()->addHour(),
            'is_active' => true,
            'sent_to_service_a' => true,
            'payload' => ['menu' => ['slug' => 'jus-jeruk', 'name' => 'Jus Jeruk']],
            'detected_at' => now()->subSeconds(20),
        ]);

        IntelligenceTrend::query()->create([
            'process_run_id' => null,
            'title' => 'Air Mineral Sedang Naik (laki-laki)',
            'image_url' => 'https://example.com/m.jpg',
            'caption' => 'M',
            'score' => 70,
            'source_timestamp' => now(),
            'expires_at' => now()->addHour(),
            'is_active' => true,
            'sent_to_service_a' => true,
            'payload' => ['menu' => ['slug' => 'air-mineral', 'name' => 'Air Mineral']],
            'detected_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($user)->get(route('intelligence.dashboard'));

        $response->assertOk();
        $response->assertSee('Carousel Perempuan');
        $response->assertSee('Carousel Laki-laki');
        $response->assertSee('Jus Jeruk Sedang Naik (perempuan)');
        $response->assertSee('Air Mineral Sedang Naik (laki-laki)');
    }
}
