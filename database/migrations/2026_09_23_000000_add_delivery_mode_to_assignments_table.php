<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('delivery_mode', 20)->default('internal')->after('type');
            $table->string('external_url', 2048)->nullable()->after('delivery_mode');
            $table->index(['type', 'delivery_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['type', 'delivery_mode']);
            $table->dropColumn(['delivery_mode', 'external_url']);
        });
    }
};
