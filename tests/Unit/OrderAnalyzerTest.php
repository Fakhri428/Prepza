<?php

namespace Tests\Unit;

use App\Services\OrderAnalyzer;
use Tests\TestCase;

class OrderAnalyzerTest extends TestCase
{
    public function test_drink_order_gets_high_priority_and_processing_status(): void
    {
        config([
            'services.service_a.busy_threshold' => 5,
            'services.service_a.overload_threshold' => 10,
        ]);

        $analyzer = new OrderAnalyzer();
        $menus = [
            ['name' => 'Es Teh', 'category' => ['name' => 'Minuman']],
        ];

        $analysis = $analyzer->analyzeOrder([
            'items' => [
                ['item_name' => 'Es Teh', 'qty' => 2],
            ],
        ], $analyzer->buildMenuTypeMap($menus), 2);

        $this->assertSame('high', $analysis['priority']);
        $this->assertSame('normal', $analysis['kitchen_status']);
        $this->assertSame('processing', $analysis['external_status']);
    }

    public function test_mixed_order_gets_medium_priority(): void
    {
        $analyzer = new OrderAnalyzer();

        $analysis = $analyzer->analyzeOrder([
            'items' => [
                ['item_name' => 'Es Teh', 'qty' => 1],
                ['item_name' => 'Nasi Bakar', 'qty' => 1],
            ],
        ], [], 3);

        $this->assertSame('medium', $analysis['priority']);
        $this->assertStringContainsString('Order campuran', $analysis['reason']);
    }

    public function test_waiting_count_over_threshold_marks_kitchen_overload(): void
    {
        config([
            'services.service_a.busy_threshold' => 5,
            'services.service_a.overload_threshold' => 8,
        ]);

        $analyzer = new OrderAnalyzer();

        $analysis = $analyzer->analyzeOrder([
            'items' => [
                ['item_name' => 'Nasi Goreng', 'qty' => 1],
            ],
        ], [], 9);

        $this->assertSame('overload', $analysis['kitchen_status']);
    }
}
