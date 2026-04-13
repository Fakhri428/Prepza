<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class GroqService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?: (string) config('services.groq.api_key');
        $this->baseUrl = (string) config('services.groq.base_url', 'https://api.groq.com/openai/v1');
        $this->model = $model ?: (string) config('services.groq.model', 'openai/gpt-oss-20b');
        $this->timeout = (int) config('services.groq.timeout', 30);

        if ($this->apiKey === '') {
            throw new RuntimeException('GROQ_API_KEY belum diatur di environment.');
        }
    }

    public function chat(string $prompt, array $messages = [], array $options = []): array
    {
        if (trim($prompt) === '' && $messages === []) {
            throw new InvalidArgumentException('Prompt atau messages wajib diisi.');
        }

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages !== []
                ? $messages
                : [['role' => 'user', 'content' => $prompt]],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? null,
        ];

        $payload = Arr::whereNotNull($payload);

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withToken($this->apiKey)
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', $payload)
                ->throw();
        } catch (RequestException $exception) {
            $errorBody = $exception->response?->json();
            throw new RuntimeException(
                'Gagal request ke Groq API: '.json_encode($errorBody, JSON_UNESCAPED_UNICODE),
                previous: $exception
            );
        }

        return $response->json();
    }

    public function chatText(string $prompt, array $messages = [], array $options = []): string
    {
        $response = $this->chat($prompt, $messages, $options);

        return (string) data_get($response, 'choices.0.message.content', '');
    }

        public function buildKitchenSupervisorPrompt(array $orders): string
        {
                $prompt = <<<'PROMPT'
You are an AI kitchen supervisor in a restaurant system.

Before making any decision:
- Analyze all orders carefully
- Consider item types, queue condition, and efficiency

Your tasks:
1. Determine kitchen condition: normal, busy, or overload
2. Decide which orders should be prioritized
3. Handle mixed orders (fast + slow items)
4. Provide actionable recommendations

Rules:
- Drinks (es, jus, minuman) are fast → high priority
- Fried food (goreng) → medium priority
- Heavy food (nasi, ayam, dll) → low priority
- If an order has mixed items:
    → prioritize fast items first
    → set priority as medium
- If many orders are waiting → overload

Return ONLY JSON with this format:

{
    "kitchen_status": "normal | busy | overload",
    "priority_orders": [
        {
            "order_id": number,
            "priority": "high | medium | low",
            "action": "prioritize | normal",
            "reason": "string"
        }
    ],
    "recommendation": "string"
}

Here is the order data:
PROMPT;

                return $prompt."\n".json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        public function analyzeKitchenOrders(array $orders, array $options = []): array
        {
                $prompt = $this->buildKitchenSupervisorPrompt($orders);
                $responseText = $this->chatText($prompt, [], array_merge(['temperature' => 0.2], $options));
                $decoded = json_decode($responseText, true);

                if (! is_array($decoded)) {
                        throw new RuntimeException('Respon Groq bukan JSON valid: '.$responseText);
                }

                return $decoded;
        }
}
