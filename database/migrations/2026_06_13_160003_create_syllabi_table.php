<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();  // deskripsi mata kuliah
            $table->text('objectives')->nullable();    // capaian pembelajaran / tujuan
            $table->text('references')->nullable();     // referensi / pustaka
            $table->text('assessment')->nullable();     // metode penilaian
            $table->text('rules')->nullable();          // aturan kelas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabi');
    }
};
