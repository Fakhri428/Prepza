<?php

namespace Database\Seeders;

use App\Models\IntelligenceOrder;
use App\Models\IntelligenceTrend;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class IntelligenceDashboardDummySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $orders = [
            [
                'service_a_order_id' => 7001,
                'order_code' => 'ORD-DMY-7001',
                'customer_name' => 'Budi',
                'gender' => 'male',
                'status' => 'waiting',
                'external_status' => 'waiting',
                'external_note' => 'Menunggu dipanggil.',
                'total_amount' => 32000,
                'queue_number' => 11,
                'queue_status' => 'waiting',
                'items' => [
                    ['service_a_item_id' => 91001, 'item_name' => 'Ayam Geprek', 'qty' => 2, 'subtotal' => 26000],
                    ['service_a_item_id' => 91002, 'item_name' => 'Es Teh', 'qty' => 1, 'subtotal' => 6000],
                ],
            ],
            [
                'service_a_order_id' => 7002,
                'order_code' => 'ORD-DMY-7002',
                'customer_name' => 'Sinta',
                'gender' => 'female',
                'status' => 'processing',
                'external_status' => 'processing',
                'external_note' => 'Sedang dimasak.',
                'total_amount' => 28000,
                'queue_number' => 12,
                'queue_status' => 'processing',
                'items' => [
                    ['service_a_item_id' => 91003, 'item_name' => 'Nasi Goreng', 'qty' => 1, 'subtotal' => 22000],
                    ['service_a_item_id' => 91004, 'item_name' => 'Es Jeruk', 'qty' => 1, 'subtotal' => 6000],
                ],
            ],
            [
                'service_a_order_id' => 7003,
                'order_code' => 'ORD-DMY-7003',
                'customer_name' => 'Rani',
                'gender' => 'female',
                'status' => 'waiting',
                'external_status' => 'waiting',
                'external_note' => 'Antrian normal.',
                'total_amount' => 45000,
                'queue_number' => 13,
                'queue_status' => 'waiting',
                'items' => [
                    ['service_a_item_id' => 91005, 'item_name' => 'Mie Ayam', 'qty' => 2, 'subtotal' => 36000],
                    ['service_a_item_id' => 91006, 'item_name' => 'Teh Hangat', 'qty' => 1, 'subtotal' => 9000],
                ],
            ],
            [
                'service_a_order_id' => 7004,
                'order_code' => 'ORD-DMY-7004',
                'customer_name' => 'Andi',
                'gender' => 'male',
                'status' => 'queued',
                'external_status' => 'waiting',
                'external_note' => 'Baru masuk queue.',
                'total_amount' => 25000,
                'queue_number' => 14,
                'queue_status' => 'waiting',
                'items' => [
                    ['service_a_item_id' => 91007, 'item_name' => 'Ayam Bakar', 'qty' => 1, 'subtotal' => 25000],
                ],
            ],
            [
                'service_a_order_id' => 7005,
                'order_code' => 'ORD-DMY-7005',
                'customer_name' => 'Lina',
                'gender' => 'female',
                'status' => 'waiting',
                'external_status' => 'processing',
                'external_note' => 'Dimasak bertahap.',
                'total_amount' => 39000,
                'queue_number' => 15,
                'queue_status' => 'processing',
                'items' => [
                    ['service_a_item_id' => 91008, 'item_name' => 'Soto Ayam', 'qty' => 1, 'subtotal' => 24000],
                    ['service_a_item_id' => 91009, 'item_name' => 'Nasi Putih', 'qty' => 1, 'subtotal' => 5000],
                    ['service_a_item_id' => 91010, 'item_name' => 'Es Teh', 'qty' => 1, 'subtotal' => 10000],
                ],
            ],
        ];

        foreach ($orders as $orderPayload) {
            $items = $orderPayload['items'];
            unset($orderPayload['items']);

            $order = IntelligenceOrder::updateOrCreate(
                ['service_a_order_id' => $orderPayload['service_a_order_id']],
                array_merge($orderPayload, [
                    'service_a_created_at' => $now->copy()->subMinutes(rand(10, 90)),
                    'external_updated_at' => $now->copy()->subMinutes(rand(1, 20)),
                    'last_synced_at' => $now,
                ])
            );

            $order->items()->delete();

            foreach ($items as $item) {
                $order->items()->create(array_merge($item, [
                    'last_synced_at' => $now,
                ]));
            }
        }

        IntelligenceTrend::query()->create([
            'process_run_id' => null,
            'title' => 'Ayam Geprek Sedang Naik (laki-laki)',
            'image_url' => 'https://example.com/trend.jpg',
            'caption' => 'Permintaan ayam geprek dari pelanggan laki-laki meningkat pada slot jam makan siang.',
            'score' => 88,
            'source_timestamp' => $now->copy()->subMinutes(5),
            'expires_at' => $now->copy()->addHours(2),
            'is_active' => true,
            'sent_to_service_a' => false,
            'payload' => [
                'title' => 'Ayam Geprek Sedang Naik (laki-laki)',
                'image_url' => 'https://example.com/trend.jpg',
                'caption' => 'Permintaan ayam geprek dari pelanggan laki-laki meningkat pada slot jam makan siang.',
                'score' => 88,
            ],
            'detected_at' => $now,
        ]);
    }
}
