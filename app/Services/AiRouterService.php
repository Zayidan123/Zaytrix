<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiRouterService
{
    private array $providers = [];
    private int $timeout;
    private bool $stream;

    public function __construct()
    {
        $this->timeout = (int) env('AI_TIMEOUT', 60);
        $this->stream = filter_var(env('AI_STREAM', true), FILTER_VALIDATE_BOOLEAN);

        // Provider 1: 9router (local)
        if ($base = env('AI_BASE_URL')) {
            $this->providers[] = [
                'name' => '9router',
                'base_url' => rtrim($base, '/') . '/chat/completions',
                'api_key' => env('AI_API_KEY'),
                'model' => env('AI_MODEL', 'kr/claude-sonnet-4.5'),
            ];
        }

        // Provider 2: OpenRouter
        if ($key = env('AI_FALLBACK_1_KEY')) {
            $this->providers[] = [
                'name' => 'OpenRouter',
                'base_url' => rtrim(env('AI_FALLBACK_1', 'https://openrouter.ai/api/v1'), '/') . '/chat/completions',
                'api_key' => $key,
                'model' => env('AI_FALLBACK_1_MODEL', 'google/gemini-2.5-flash'),
            ];
        }

        // Provider 3: Google Gemini
        if ($key = env('AI_FALLBACK_2_KEY')) {
            $this->providers[] = [
                'name' => 'Gemini',
                'base_url' => rtrim(env('AI_FALLBACK_2', 'https://generativelanguage.googleapis.com/v1beta'), '/') . '/models/' . env('AI_FALLBACK_2_MODEL', 'gemini-2.5-flash') . ':streamGenerateContent',
                'api_key' => $key,
                'model' => env('AI_FALLBACK_2_MODEL', 'gemini-2.5-flash'),
            ];
        }
    }

    public function chat(string $prompt, array $messages = [], array $options = []): array
    {
        $messages = array_merge([
            ['role' => 'system', 'content' => 'You are Zaytrix AI, a crypto trading assistant with access to on-chain data and technical analysis.'],
        ], $messages, [
            ['role' => 'user', 'content' => $prompt],
        ]);

        $payload = [
            'model' => $options['model'] ?? $this->providers[0]['model'] ?? 'kr/claude-sonnet-4.5',
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? env('AI_TEMPERATURE', 0.7),
            'max_tokens' => $options['max_tokens'] ?? env('AI_MAX_TOKENS', 2000),
            'stream' => false,
        ];

        foreach ($this->providers as $provider) {
            try {
                $start = hrtime(true);
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->authHeaders($provider))
                    ->post($provider['base_url'], array_merge($payload, ['model' => $provider['model']]));
                $elapsed = (hrtime(true) - $start) / 1e6;

                if ($response->successful()) {
                    $data = $response->json();
                    $this->logUsage($provider['name'], $data, $elapsed);
                    return ['content' => $data['choices'][0]['message']['content'] ?? '', 'provider' => $provider['name'], 'usage' => $data['usage'] ?? []];
                }
                Log::warning("AI Provider {$provider['name']} failed", ['status' => $response->status(), 'body' => $response->body()]);
            } catch (\Throwable $e) {
                Log::warning("AI Provider {$provider['name']} exception", ['error' => $e->getMessage()]);
            }
        }

        return ['content' => 'Error: All AI providers failed.', 'provider' => 'none', 'usage' => []];
    }

    public function streamChat(string $prompt, callable $onChunk, array $messages = []): void
    {
        $messages = array_merge([
            ['role' => 'system', 'content' => 'You are Zaytrix AI, a crypto trading assistant with access to on-chain data and technical analysis.'],
        ], $messages, [
            ['role' => 'user', 'content' => $prompt],
        ]);

        $payload = [
            'model' => $this->providers[0]['model'] ?? 'kr/claude-sonnet-4.5',
            'messages' => $messages,
            'temperature' => env('AI_TEMPERATURE', 0.7),
            'max_tokens' => env('AI_MAX_TOKENS', 2000),
            'stream' => true,
        ];

        foreach ($this->providers as $provider) {
            try {
                $start = hrtime(true);
                $client = Http::timeout($this->timeout)
                    ->withHeaders($this->authHeaders($provider))
                    ->accept('text/event-stream')
                    ->post($provider['base_url'], array_merge($payload, ['model' => $provider['model']]));

                if (!$client->successful()) {
                    Log::warning("Stream Provider {$provider['name']} failed", ['status' => $client->status()]);
                    continue;
                }

                $buffer = '';
                foreach ($client->getBody()->getStream() as $chunk) {
                    $buffer .= $chunk;
                    $lines = explode("\n", $buffer);
                    $buffer = array_pop($lines);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (str_starts_with($line, 'data: ')) {
                            $data = substr($line, 6);
                            if ($data === '[DONE]') {
                                $elapsed = (hrtime(true) - $start) / 1e6;
                                $this->logUsage($provider['name'], ['usage' => ['total_tokens' => 0]], $elapsed);
                                return;
                            }
                            $decoded = json_decode($data, true);
                            $content = $decoded['choices'][0]['delta']['content'] ?? '';
                            if ($content) $onChunk($content);
                        }
                    }
                }
                $elapsed = (hrtime(true) - $start) / 1e6;
                $this->logUsage($provider['name'], ['usage' => ['total_tokens' => 0]], $elapsed);
                return;
            } catch (\Throwable $e) {
                Log::warning("Stream Provider {$provider['name']} exception", ['error' => $e->getMessage()]);
            }
        }
        $onChunk('[Error: All AI providers failed.]');
    }

    private function authHeaders(array $provider): array
    {
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        if (!empty($provider['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $provider['api_key'];
        }
        return $headers;
    }

    private function logUsage(string $provider, array $data, float $elapsed): void
    {
        $usage = $data['usage'] ?? [];
        \App\Models\AiUsageLog::create([
            'user_id' => auth()->id() ?? 1,
            'provider' => $provider,
            'model' => $this->providers[0]['model'] ?? 'unknown',
            'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'response_time_ms' => (int) $elapsed,
            'cost_estimate' => 0,
            'context_used' => null,
        ]);
    }
}
