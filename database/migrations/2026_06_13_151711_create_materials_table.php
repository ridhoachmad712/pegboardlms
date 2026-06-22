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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['file', 'link', 'video']);
            $table->string('path')->nullable();   // relatif ke disk 'public' untuk file
            $table->string('url')->nullable();     // untuk link / video
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->timestamps();

            $table->index('meeting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
