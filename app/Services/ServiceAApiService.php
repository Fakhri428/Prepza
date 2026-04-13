<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ServiceAApiService
{
    public function fetchMenus(): array
    {
        $response = $this->client()->get('/api/menus');
        $this->throwIfFailed($response->status(), $response->body());

        return Arr::get($response->json(), 'data', []);
    }

    public function fetchQueueOrders(?string $statuses = null): array
    {
        $queryStatuses = $statuses ?? (string) config('services.service_a.fetch_statuses', 'queued,waiting,processing');

        $response = $this->client()->get('/api/queue/orders', [
            'status' => $queryStatuses,
        ]);

        $this->throwIfFailed($response->status(), $response->body());

        return Arr::get($response->json(), 'data', []);
    }

    public function patchExternalUpdate(int $orderId, array $payload): array
    {
        $response = $this->client()->patch("/api/queue/orders/{$orderId}/external-update", $payload);
        $this->throwIfFailed($response->status(), $response->body());

        return (array) $response->json();
    }

    public function postTrendUpdate(array $payload): array
    {
        $response = $this->client()->post('/api/queue/trends/update', $payload);
        $this->throwIfFailed($response->status(), $response->body());

        return (array) $response->json();
    }

    protected function client(): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('services.service_a.base_url'), '/'))
            ->timeout((int) config('services.service_a.timeout', 20))
            ->retry(
                (int) config('services.service_a.retry_times', 2),
                (int) config('services.service_a.retry_sleep_ms', 250)
            )
            ->acceptJson();

        $token = (string) config('services.service_a.token');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    protected function throwIfFailed(int $statusCode, string $body): void
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        throw new RuntimeException("Service A request gagal ({$statusCode}): {$body}");
    }
}
