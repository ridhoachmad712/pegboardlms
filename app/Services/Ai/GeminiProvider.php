<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AiProvider
{
    private ?string $key;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->key = Setting::get('ai_key_gemini') ?: config('services.gemini.key');
        $this->model = Setting::get('ai_model_gemini') ?: config('services.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
    }

    public function label(): string
    {
        return 'Google Gemini — '.$this->model;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->key);
    }

    public function complete(string $system, string $prompt, int $maxTokens): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('API key Gemini belum dikonfigurasi.');
        }

        $url = $this->baseUrl.'/v1beta/models/'.$this->model.':generateContent';

        $response = Http::withHeaders(['content-type' => 'application/json'])
            ->timeout(60)
            ->post($url.'?key='.urlencode($this->key), [
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['maxOutputTokens' => $maxTokens],
            ]);

        if ($response->failed()) {
            $msg = $response->json('error.message') ?? $response->body();
            throw new RuntimeException('Gemini error ('.$response->status().'): '.$msg);
        }

        return collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')->implode("\n");
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'API key Gemini belum diisi.'];
        }
        try {
            $this->complete('You are an assistant.', 'ping', 8);

            return ['ok' => true, 'message' => 'Koneksi Gemini berhasil — model "'.$this->model.'" aktif.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
