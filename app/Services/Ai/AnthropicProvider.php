<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicProvider implements AiProvider
{
    private ?string $key;
    private string $model;
    private string $baseUrl;
    private string $version;

    public function __construct()
    {
        // ai_key_anthropic baru; ai_api_key lama dipertahankan utk kompatibilitas; lalu .env
        $this->key = Setting::get('ai_key_anthropic') ?: Setting::get('ai_api_key') ?: config('services.anthropic.key');
        $this->model = Setting::get('ai_model_anthropic') ?: Setting::get('ai_model') ?: config('services.anthropic.model');
        $this->baseUrl = rtrim(config('services.anthropic.base_url'), '/');
        $this->version = config('services.anthropic.version');
    }

    public function label(): string
    {
        return 'Claude (Anthropic) — '.$this->model;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->key);
    }

    public function complete(string $system, string $prompt, int $maxTokens): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('API key Anthropic belum dikonfigurasi.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->key,
            'anthropic-version' => $this->version,
            'content-type' => 'application/json',
        ])->timeout(60)->post($this->baseUrl.'/v1/messages', [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? $response->body();
            throw new RuntimeException('Anthropic error ('.$response->status().'): '.$msg);
        }

        return collect($response->json('content', []))
            ->where('type', 'text')->pluck('text')->implode("\n");
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'API key Anthropic belum diisi.'];
        }
        try {
            $this->complete('Anda asisten.', 'ping', 8);

            return ['ok' => true, 'message' => 'Koneksi Claude berhasil — model "'.$this->model.'" aktif.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
