<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained()->cascadeOnDelete();
            $table->decimal('points', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['submission_id', 'rubric_criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_scores');
    }
};
