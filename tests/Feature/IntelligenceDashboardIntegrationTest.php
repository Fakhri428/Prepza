<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\IntelligenceDashboardDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
