<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assignments', 'instruction_file_path')) {
            Schema::table('assignments', fn (Blueprint $table) => $table->string('instruction_file_path')->nullable()->after('description'));
        }
        if (! Schema::hasColumn('assignments', 'instruction_file_name')) {
            Schema::table('assignments', fn (Blueprint $table) => $table->string('instruction_file_name')->nullable()->after('instruction_file_path'));
        }
        if (! Schema::hasColumn('assignments', 'instruction_file_mime')) {
            Schema::table('assignments', fn (Blueprint $table) => $table->string('instruction_file_mime')->nullable()->after('instruction_file_name'));
        }
        if (! Schema::hasColumn('assignments', 'instruction_file_size')) {
            Schema::table('assignments', fn (Blueprint $table) => $table->unsignedBigInteger('instruction_file_size')->nullable()->after('instruction_file_mime'));
        }
    }

    public function down(): void
    {
        foreach (['instruction_file_size', 'instruction_file_mime', 'instruction_file_name', 'instruction_file_path'] as $column) {
            if (Schema::hasColumn('assignments', $column)) {
                Schema::table('assignments', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
