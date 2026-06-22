<?php

namespace App\Services\Ai;

interface AiProvider
{
    /** Apakah API key provider ini sudah terisi. */
    public function isConfigured(): bool;

    /** Kirim satu permintaan (system + prompt), kembalikan teks jawaban. */
    public function complete(string $system, string $prompt, int $maxTokens): string;

    /** Uji koneksi & validitas key. @return array{ok:bool,message:string} */
    public function testConnection(): array;

    /** Nama tampilan provider. */
    public function label(): string;
}
