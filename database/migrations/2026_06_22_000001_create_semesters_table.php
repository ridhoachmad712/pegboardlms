<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('semester'); // Ganjil | Genap | Antara
            $table->timestamps();

            $table->unique(['year', 'semester']);
        });

        // Backfill: ambil semua periode (tahun + semester) dari kelas yang sudah ada,
        // termasuk yang ada di tong sampah, supaya daftar lama tetap muncul.
        $periods = DB::table('courses')
            ->select('year', 'semester')
            ->whereNotNull('year')
            ->whereNotNull('semester')
            ->distinct()
            ->get();

        foreach ($periods as $p) {
            DB::table('semesters')->insertOrIgnore([
                'year' => $p->year,
                'semester' => $p->semester,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
