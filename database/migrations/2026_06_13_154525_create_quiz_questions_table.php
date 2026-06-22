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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->enum('type', ['pg', 'essay'])->default('pg');
            $table->json('options')->nullable();   // ['A'=>..,'B'=>..] untuk PG
            $table->string('correct_answer')->nullable(); // kunci PG (key opsi)
            $table->unsignedInteger('points')->default(1);
            $table->timestamps();

            $table->index('assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
