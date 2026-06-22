<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syllabi', function (Blueprint $table) {
            $table->text('cpl')->nullable()->after('description');       // Capaian Pembelajaran Lulusan
            $table->text('cpmk')->nullable()->after('cpl');             // Capaian Pembelajaran Mata Kuliah
            $table->text('sub_cpmk')->nullable()->after('cpmk');        // Sub-CPMK
        });

        // Data lama "objectives" (Capaian Pembelajaran) dipindah ke CPMK
        DB::table('syllabi')->whereNotNull('objectives')->update([
            'cpmk' => DB::raw('objectives'),
        ]);

        Schema::table('syllabi', function (Blueprint $table) {
            $table->dropColumn('objectives');
        });
    }

    public function down(): void
    {
        Schema::table('syllabi', function (Blueprint $table) {
            $table->text('objectives')->nullable()->after('description');
        });

        DB::table('syllabi')->whereNotNull('cpmk')->update([
            'objectives' => DB::raw('cpmk'),
        ]);

        Schema::table('syllabi', function (Blueprint $table) {
            $table->dropColumn(['cpl', 'cpmk', 'sub_cpmk']);
        });
    }
};
