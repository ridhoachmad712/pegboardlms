<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nilai manual per komponen (untuk komponen tanpa tugas online, mis. UTS/UAS kertas)
        Schema::create('grade_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable(); // skala 0–100
            $table->timestamps();

            $table->unique(['grade_component_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_scores');
    }
};
