<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Intelligence Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errorMessage)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <p class="font-semibold">Gagal mengambil data dari Service A</p>
                    <p class="text-sm mt-1">{{ $errorMessage }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-4 border border-gray-100">
                    <p class="text-sm text-gray-500">Total Order Aktif</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $summary['total_orders'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 border border-gray-100">
                    <p class="text-sm text-gray-500">Waiting Count</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $summary['waiting_count'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 border border-gray-100">
                    <p class="text-sm text-gray-500">Priority High</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $summary['priority_counts']['high'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 border border-gray-100">
                    <p class="text-sm text-gray-500">Priority Medium</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $summary['priority_counts']['medium'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 border border-gray-100">
                    <p class="text-sm text-gray-500">Priority Low</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2">{{ $summary['priority_counts']['low'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white shadow-sm rounded-lg border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Analisis Queue Orders</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                            Kitchen: {{ strtoupper($summary['kitchen_status']) }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Items</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <div class="font-medium text-gray-900">{{ $order['order_code'] }}</div>
                                            <div class="text-xs text-gray-500">Status: {{ $order['status'] }} | External: {{ $order['external_status'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $order['customer_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $order['item_count'] }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                                                {{ strtoupper($order['priority']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $order['reason'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                            Tidak ada data order aktif.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-100">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Trend Insight</h3>
                    </div>

                    <div class="p-5 space-y-3">
                        @if ($trend)
                            <img src="{{ $trend['image_url'] }}" alt="Trend" class="w-full h-36 object-cover rounded-lg border border-gray-100">
                            <h4 class="font-semibold text-gray-900">{{ $trend['title'] }}</h4>
                            <p class="text-sm text-gray-600">{{ $trend['caption'] }}</p>
                            <div class="text-xs text-gray-500 space-y-1">
                                <p>Score: {{ $trend['score'] }}</p>
                                <p>Source: {{ $trend['source_timestamp'] }}</p>
                                <p>Expires: {{ $trend['expires_at'] }}</p>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">Belum ada insight tren yang memenuhi threshold.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
