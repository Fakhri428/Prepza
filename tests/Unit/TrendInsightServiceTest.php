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
        ], [
            [
                'id' => 10,
                'name' => 'Ayam Geprek',
                'slug' => 'ayam-geprek',
                'description' => 'Ayam geprek pedas',
                'image_path' => 'menus/ayam-geprek.jpg',
                'image_external_url' => null,
                'image_url' => '/storage/menus/ayam-geprek.jpg',
                'price' => '22000.00',
                'is_active' => true,
                'aliases' => [
                    ['alias' => 'geprek'],
                ],
            ],
            [
                'id' => 20,
                'name' => 'Salad Buah',
                'slug' => 'salad-buah',
                'description' => 'Salad buah segar',
                'image_path' => 'menus/salad-buah.jpg',
                'image_external_url' => null,
                'image_url' => '/storage/menus/salad-buah.jpg',
                'price' => '18000.00',
                'is_active' => true,
            ],
        ]);

        $this->assertCount(2, $payloads);

        $titles = array_column($payloads, 'title');
        $this->assertContains('Ayam Geprek Sedang Naik (laki-laki)', $titles);
        $this->assertContains('Salad Buah Sedang Naik (perempuan)', $titles);

        $malePayload = collect($payloads)->first(fn (array $payload) => str_contains((string) $payload['title'], '(laki-laki)'));
        $this->assertNotNull($malePayload);
        $this->assertSame('Ayam Geprek', $malePayload['menu']['name']);
        $this->assertSame('ayam-geprek', $malePayload['menu']['slug']);
        $this->assertSame('Ayam geprek pedas', $malePayload['menu']['description']);
        $this->assertSame('menus/ayam-geprek.jpg', $malePayload['menu']['image_path']);
        $this->assertSame('/storage/menus/ayam-geprek.jpg', $malePayload['menu']['image_url']);
        $this->assertSame('22000.00', $malePayload['menu']['price']);
        $this->assertTrue($malePayload['menu']['is_active']);
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
