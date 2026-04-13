<?php

namespace Tests\Unit;

use App\Services\TrendInsightService;
use Tests\TestCase;

class TrendInsightServiceTest extends TestCase
{
    public function test_builds_separate_trend_payloads_for_male_and_female(): void
    {
        config([
            'services.service_a.trend_min_repeat' => 1,
            'services.service_a.trend_placeholder_image' => 'https://example.com/trend.jpg',
            'services.service_a.trend_expire_minutes' => 30,
        ]);

        $service = new TrendInsightService();

        $payloads = $service->buildTrendPayloadsByGender([
            [
                'gender' => 'male',
                'items' => [
                    ['item_name' => 'Ayam Geprek', 'qty' => 2],
                ],
            ],
            [
                'gender' => 'female',
                'items' => [
                    ['item_name' => 'Salad Buah', 'qty' => 3],
                ],
            ],
        ]);

        $this->assertCount(2, $payloads);

        $titles = array_column($payloads, 'title');
        $this->assertContains('Ayam Geprek Sedang Naik (laki-laki)', $titles);
        $this->assertContains('Salad Buah Sedang Naik (perempuan)', $titles);
    }

    public function test_falls_back_to_generic_trend_when_gender_unknown(): void
    {
        config([
            'services.service_a.trend_min_repeat' => 1,
            'services.service_a.trend_placeholder_image' => 'https://example.com/trend.jpg',
        ]);

        $service = new TrendInsightService();

        $payloads = $service->buildTrendPayloadsByGender([
            [
                'gender' => 'unknown',
                'items' => [
                    ['item_name' => 'Nasi Goreng', 'qty' => 2],
                ],
            ],
        ]);

        $this->assertCount(1, $payloads);
        $this->assertSame('Nasi Goreng Sedang Naik', $payloads[0]['title']);
    }
}
