<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // mahasiswa
            $table->string('file_path')->nullable();    // tugas: berkas
            $table->decimal('score', 5, 2)->nullable(); // nilai akhir submission
            $table->text('feedback')->nullable();
            $table->enum('status', ['ontime', 'late'])->default('ontime');
            $table->timestamp('started_at')->nullable();   // kuis: mulai
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'user_id']); // satu submission per tugas/kuis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
