<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom dibuat sebagai enum('active','archived') → CHECK constraint di SQLite.
        // Ubah jadi string biasa agar nilai 'completed' diterima.
        Schema::table('courses', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });

        DB::table('courses')->where('status', 'archived')->update(['status' => 'completed']);
    }

    public function down(): void
    {
        DB::table('courses')->where('status', 'completed')->update(['status' => 'archived']);
    }
};
