<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoReset extends Command
{
    protected $signature = 'lms:demo-reset {--force : Lewati konfirmasi}';

    protected $description = 'Reset database demo ke kondisi awal (HANYA saat DEMO_MODE=true)';

    /** Pengaturan branding yang DIPERTAHANKAN lintas reset (diatur sekali via menu Tampilan). */
    private const BRANDING_KEYS = [
        'app_name', 'header_title', 'hide_header_title', 'theme_color', 'footer_text',
        'logo_path', 'logo_dark_path', 'logo_height', 'favicon_path',
    ];

    public function handle(): int
    {
        // Pengaman: tolak total kalau bukan instance demo, agar data asli tak pernah terhapus.
        if (! config('demo.enabled')) {
            $this->error('Dibatalkan: lms:demo-reset hanya bisa dijalankan saat DEMO_MODE=true.');

            return self::FAILURE;
        }

        // Pengaman ganda: jangan pernah jalan di environment lokal (mesin pengembangan),
        // walaupun DEMO_MODE kebetulan true. Instance demo berjalan di APP_ENV=production.
        if (app()->environment('local')) {
            $this->error('Dibatalkan: lms:demo-reset tidak boleh dijalankan di environment lokal (APP_ENV=local).');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Ini akan MENGHAPUS semua data dan mengisi ulang data demo. Lanjutkan?')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        // Simpan branding yang sudah diatur SEBELUM database dikosongkan.
        $branding = [];
        try {
            $branding = DB::table('settings')
                ->whereIn('key', self::BRANDING_KEYS)
                ->pluck('value', 'key')
                ->all();
        } catch (\Throwable $e) {
            // tabel belum ada (deploy pertama) — abaikan
        }

        $this->info('Mengosongkan database…');
        $this->call('migrate:fresh', ['--force' => true]);

        $this->info('Mengisi data demo…');
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        // Pulihkan branding (menimpa default seeder) agar logo/header/footer/favicon tetap.
        foreach ($branding as $key => $value) {
            Setting::put($key, $value);
        }
        if ($branding !== []) {
            $this->info('Branding dipertahankan: '.implode(', ', array_keys($branding)));
        }

        $this->info('Selesai. Database demo sudah direset.');

        return self::SUCCESS;
    }
}
