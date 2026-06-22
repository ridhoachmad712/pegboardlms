<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoReset extends Command
{
    protected $signature = 'lms:demo-reset {--force : Lewati konfirmasi}';

    protected $description = 'Reset database demo ke kondisi awal (HANYA saat DEMO_MODE=true)';

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

        $this->info('Mengosongkan database…');
        $this->call('migrate:fresh', ['--force' => true]);

        $this->info('Mengisi data demo…');
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->info('Selesai. Database demo sudah direset.');

        return self::SUCCESS;
    }
}
