<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lepas batasan enum ('qr','manual') menjadi string bebas agar mendukung
        // metode 'mandiri' (swa-presensi pertemuan Full LMS).
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('method')->default('manual')->change();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('method', ['qr', 'manual'])->default('manual')->change();
        });
    }
};
