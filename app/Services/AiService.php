<?php

namespace App\Services;

use App\Services\Ai\AiProvider;
use App\Services\Ai\AiProviderFactory;
use RuntimeException;

/**
 * Orkestrator AI: membangun prompt (ringkasan/soal) lalu memanggil provider aktif
 * (Claude / OpenAI / Gemini). Logika prompt & parsing dipakai bersama semua provider.
 */
class AiService
{
    private AiProvider $provider;

    public function __construct()
    {
        $this->provider = AiProviderFactory::make();
    }

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    public function testConnection(): array
    {
        return $this->provider->testConnection();
    }

    /** Ringkasan materi dalam Bahasa Indonesia, poin-poin. */
    public function summarize(string $text): string
    {
        $out = $this->provider->complete(
            'Anda asisten akademik. Buat ringkasan materi kuliah dalam Bahasa Indonesia, '
                .'terstruktur dengan poin-poin utama yang jelas dan ringkas (gunakan bullet).',
            "Ringkas materi kuliah berikut:\n\n".$this->clip($text),
            1500,
        );

        return trim($out);
    }

    /**
     * Susun materi kuliah (Markdown) berdasarkan RPS + topik pertemuan + materi yang ada.
     *
     * @param  array{course?:string, topic?:string, cpl?:?string, cpmk?:?string, sub_cpmk?:?string, description?:?string, references?:?string, sources?:?string}  $ctx
     */
    public function generateMaterial(array $ctx): string
    {
        $system = 'Anda dosen ahli yang menulis materi kuliah. Tulis materi pembelajaran yang terstruktur, '
            .'akurat, dan mudah dipahami dalam Bahasa Indonesia memakai format Markdown (judul ##, sub-judul ###, '
            .'poin-poin, tabel/contoh bila relevan). Selaraskan isi dengan capaian pembelajaran (CPL/CPMK/Sub-CPMK) '
            .'dan topik. Langsung ke materi tanpa basa-basi pembuka/penutup.';

        $parts = [];
        $add = function (string $label, ?string $val) use (&$parts) {
            if (filled($val)) {
                $parts[] = $label.":\n".trim($val);
            }
        };
        $add('Mata kuliah', $ctx['course'] ?? null);
        $add('Topik pertemuan', $ctx['topic'] ?? null);
        $add('CPL (Capaian Pembelajaran Lulusan)', $ctx['cpl'] ?? null);
        $add('CPMK (Capaian Pembelajaran Mata Kuliah)', $ctx['cpmk'] ?? null);
        $add('Sub-CPMK', $ctx['sub_cpmk'] ?? null);
        $add('Deskripsi mata kuliah', $ctx['description'] ?? null);
        $add('Referensi', $ctx['references'] ?? null);
        if (filled($ctx['sources'] ?? null)) {
            $parts[] = "Cuplikan materi PDF yang sudah ada (perluas & rujuk, jangan sekadar menyalin):\n".$this->clip($ctx['sources'], 20000);
        }

        $out = $this->provider->complete(
            $system,
            "Susun materi kuliah yang lengkap dan runtut untuk pertemuan ini berdasarkan informasi berikut:\n\n".implode("\n\n", $parts),
            4000,
        );

        return trim($out);
    }

    /**
     * Hasilkan soal pilihan ganda dari teks materi.
     *
     * @return array<int, array{question:string, options:array<string,string>, correct_answer:string, points:int}>
     */
    public function generateQuestions(string $text, int $count = 5): array
    {
        $system = 'Anda penyusun soal. Hasilkan HANYA JSON valid (tanpa teks lain, tanpa code fence) '
            .'berupa array objek dengan bentuk: '
            .'[{"question": "...", "options": {"A": "...", "B": "...", "C": "...", "D": "..."}, "correct_answer": "A", "points": 1}]. '
            .'Gunakan Bahasa Indonesia.';

        $raw = $this->provider->complete(
            $system,
            "Buat $count soal pilihan ganda dari materi berikut:\n\n".$this->clip($text),
            3000,
        );

        return $this->parseQuestions($raw);
    }

    /** @return array<int, array> */
    private function parseQuestions(string $raw): array
    {
        $clean = trim(preg_replace('/^```(?:json)?|```$/m', '', $raw));
        $start = strpos($clean, '[');
        $end = strrpos($clean, ']');
        if ($start !== false && $end !== false) {
            $clean = substr($clean, $start, $end - $start + 1);
        }

        $data = json_decode($clean, true);
        if (! is_array($data)) {
            throw new RuntimeException('Gagal mengurai JSON soal dari respons AI.');
        }

        $questions = [];
        foreach ($data as $q) {
            if (empty($q['question']) || empty($q['options']) || empty($q['correct_answer'])) {
                continue;
            }
            $questions[] = [
                'question' => (string) $q['question'],
                'options' => array_map('strval', $q['options']),
                'correct_answer' => (string) $q['correct_answer'],
                'points' => (int) ($q['points'] ?? 1),
            ];
        }

        return $questions;
    }

    private function clip(string $text, int $max = 40000): string
    {
        $text = trim($text);

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) : $text;
    }
}
