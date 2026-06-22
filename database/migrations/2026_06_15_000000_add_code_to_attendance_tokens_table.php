<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_tokens', function (Blueprint $table) {
            $table->string('code', 6)->nullable()->after('token');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_tokens', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropColumn('code');
        });
    }
};
