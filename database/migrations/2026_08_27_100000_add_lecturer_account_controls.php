<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'lecturer_disabled_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->timestamp('lecturer_disabled_at')->nullable());
        }
        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('must_change_password')->default(false));
        }
        if (! Schema::hasColumn('users', 'lecturer_session_version')) {
            Schema::table('users', fn (Blueprint $table) => $table->unsignedInteger('lecturer_session_version')->default(0));
        }
        if (! Schema::hasIndex('users', ['lecturer_disabled_at'])) {
            Schema::table('users', fn (Blueprint $table) => $table->index('lecturer_disabled_at'));
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', ['lecturer_disabled_at'])) {
            Schema::table('users', fn (Blueprint $table) => $table->dropIndex(['lecturer_disabled_at']));
        }
        foreach (['lecturer_disabled_at', 'must_change_password', 'lecturer_session_version'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
