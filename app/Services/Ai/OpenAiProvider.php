<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AiProvider
{
    private ?string $key;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->key = Setting::get('ai_key_openai') ?: config('services.openai.key');
        $this->model = Setting::get('ai_model_openai') ?: config('services.openai.model', 'gpt-4o-mini');
        $this->baseUrl = rtrim(config('services.openai.base_url', 'https://api.openai.com'), '/');
    }

    public function label(): string
    {
        return 'OpenAI (ChatGPT) — '.$this->model;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->key);
    }

    public function complete(string $system, string $prompt, int $maxTokens): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('API key OpenAI belum dikonfigurasi.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->key,
            'content-type' => 'application/json',
        ])->timeout(60)->post($this->baseUrl.'/v1/chat/completions', [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? $response->body();
            throw new RuntimeException('OpenAI error ('.$response->status().'): '.$msg);
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'API key OpenAI belum diisi.'];
        }
        try {
            $this->complete('You are an assistant.', 'ping', 8);

            return ['ok' => true, 'message' => 'Koneksi OpenAI berhasil — model "'.$this->model.'" aktif.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
