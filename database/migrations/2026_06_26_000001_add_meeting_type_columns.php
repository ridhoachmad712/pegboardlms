<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Jenis pertemuan default saat menambah pertemuan baru di kelas ini.
            $table->string('default_meeting_type')->default('tatap_muka')->after('status');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('type')->default('tatap_muka')->after('topic'); // tatap_muka | mandiri
            $table->string('location')->nullable()->after('type');         // ruang/lokasi (tatap muka)
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('default_meeting_type');
        });
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['type', 'location']);
        });
    }
};
