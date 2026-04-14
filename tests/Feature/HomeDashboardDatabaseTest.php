<?php

namespace Tests\Feature;

use App\Models\IntelligenceOrder;
use App\Models\User;
use Database\Seeders\IntelligenceDashboardDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeDashboardDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_dashboard_uses_database_metrics_instead_of_dummy_values(): void
    {
        $this->seed(IntelligenceDashboardDummySeeder::class);

        IntelligenceOrder::query()
            ->where('service_a_order_id', 7002)
            ->update([
                'status' => 'done',
                'external_status' => 'done',
                'external_updated_at' => now(),
                'total_amount' => 28000,
            ]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return isset($stats['daily_revenue'], $stats['monthly_revenue'], $stats['orders_today'], $stats['orders_month'])
                && $stats['completed_today'] >= 1
                && (float) $stats['daily_revenue'] >= 28000
                && (float) $stats['monthly_revenue'] >= (float) $stats['daily_revenue']
                && (int) $stats['orders_today'] >= 1
                && (int) $stats['orders_month'] >= (int) $stats['orders_today'];
        });

        $response->assertViewHas('throughputChart', fn (array $chart): bool => count($chart) === 24);
        $response->assertViewHas('statusDistribution', fn (array $distribution): bool => count($distribution) === 4);
        $response->assertSee('Data real-time dari database lokal');
        $response->assertSee('Total Pemasukan Harian');
        $response->assertSee('Total Pemasukan Bulanan');
        $response->assertSee('Total Order Hari Ini');
        $response->assertSee('Total Order Bulan Ini');
    }
}
