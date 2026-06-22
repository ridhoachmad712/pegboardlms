<?php

namespace App\Services\Ai;

use App\Models\Setting;

class AiProviderFactory
{
    /** Daftar model per provider (id => label) untuk dropdown & validasi. */
    public static function models(): array
    {
        return [
            'anthropic' => [
                'claude-haiku-4-5' => 'Haiku 4.5 — hemat & cepat',
                'claude-sonnet-4-6' => 'Sonnet 4.6 — seimbang',
                'claude-opus-4-8' => 'Opus 4.8 — paling cerdas',
            ],
            'openai' => [
                'gpt-4o-mini' => 'GPT-4o mini — hemat',
                'gpt-4o' => 'GPT-4o',
                'gpt-4.1-mini' => 'GPT-4.1 mini',
                'gpt-4.1' => 'GPT-4.1',
            ],
            'gemini' => [
                'gemini-2.5-flash' => 'Gemini 2.5 Flash — hemat',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                'gemini-2.5-pro' => 'Gemini 2.5 Pro',
            ],
        ];
    }

    public static function labels(): array
    {
        return [
            'anthropic' => 'Claude (Anthropic)',
            'openai' => 'OpenAI (ChatGPT)',
            'gemini' => 'Google Gemini',
        ];
    }

    /** Provider yang sedang aktif. */
    public static function active(): string
    {
        $p = Setting::get('ai_provider');

        return array_key_exists((string) $p, self::labels()) ? $p : 'anthropic';
    }

    public static function make(?string $name = null): AiProvider
    {
        return match ($name ?? self::active()) {
            'openai' => new OpenAiProvider(),
            'gemini' => new GeminiProvider(),
            default => new AnthropicProvider(),
        };
    }
}
