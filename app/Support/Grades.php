<?php

namespace App\Support;

class Grades
{
    /** Skala default bila belum diatur dosen. */
    private const DEFAULT_SCALE = [
        ['letter' => 'A', 'min' => 85],
        ['letter' => 'B+', 'min' => 80],
        ['letter' => 'B', 'min' => 75],
        ['letter' => 'C+', 'min' => 70],
        ['letter' => 'C', 'min' => 65],
        ['letter' => 'D', 'min' => 60],
        ['letter' => 'E', 'min' => 0],
    ];

    private static ?array $cache = null;

    /** Skala default (untuk reset / tampilan awal). */
    public static function defaultScale(): array
    {
        return self::DEFAULT_SCALE;
    }

    /** Skala nilai aktif (urut menurun berdasarkan batas minimum). */
    public static function scale(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $json = \App\Models\Setting::get('grade_scale');
        $rows = $json ? json_decode($json, true) : null;

        if (! is_array($rows) || empty($rows)) {
            return self::$cache = self::DEFAULT_SCALE;
        }

        $clean = [];
        foreach ($rows as $r) {
            if (! isset($r['letter']) || trim((string) $r['letter']) === '') {
                continue;
            }
            $clean[] = ['letter' => (string) $r['letter'], 'min' => (float) ($r['min'] ?? 0)];
        }

        usort($clean, fn ($a, $b) => $b['min'] <=> $a['min']);

        return self::$cache = ($clean ?: self::DEFAULT_SCALE);
    }

    /** Lupakan cache (setelah skala diubah dalam request yang sama). */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /** Konversi nilai angka (0-100) ke huruf sesuai skala aktif. */
    public static function letter(?float $score): string
    {
        if (is_null($score)) {
            return '-';
        }

        $scale = self::scale();
        foreach ($scale as $row) {
            if ($score >= $row['min']) {
                return $row['letter'];
            }
        }

        return end($scale)['letter'] ?? 'E';
    }

    /** Warna badge Tabler berdasarkan huruf awal. */
    public static function color(string $letter): string
    {
        return match (strtoupper(substr($letter, 0, 1))) {
            'A' => 'green',
            'B' => 'lime',
            'C' => 'yellow',
            'D' => 'orange',
            'E', 'F' => 'red',
            default => 'secondary',
        };
    }
}
