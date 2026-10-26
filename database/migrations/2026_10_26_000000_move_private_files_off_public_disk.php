<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Berkas materi & pengumpulan tidak boleh lagi dapat dibuka langsung lewat /storage
 * (tanpa cek akses). Pindahkan berkas lama ke disk privat saat `migrate --force`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tes memakai Storage::fake & menguji perintahnya langsung; jangan sentuh storage asli.
        if (app()->runningUnitTests()) {
            return;
        }

        // Gagal → migrasi tidak dicatat sehingga `migrate` berikutnya mengulang (perintah aman diulang).
        if (Artisan::call('lms:privatize-files') !== 0) {
            throw new RuntimeException('Sebagian berkas gagal dipindahkan: '.trim(Artisan::output()));
        }
    }

    public function down(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Artisan::call('lms:privatize-files', ['--reverse' => true]);
    }
};
