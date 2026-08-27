<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may retain earlier DDL statements after a later statement fails.
        // Inspect each object separately so an unfinished migration can be resumed.
        $activationExists = Schema::hasColumn('users', 'lecturer_activated_at');
        $codesExist = Schema::hasTable('lecturer_activation_codes');

        // Only a first-time installation can safely identify legacy lecturers.
        // On a retry, a NULL activation date may belong to an unpaid new account.
        $legacyLecturers = ! $activationExists && ! $codesExist
            ? DB::table('users')->where('role', 'dosen')->pluck('id')->all()
            : [];

        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false);
            });
        }
        if (! $activationExists) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('lecturer_activated_at')->nullable();
            });
            foreach (array_chunk($legacyLecturers, 500) as $ids) {
                DB::table('users')->whereIn('id', $ids)->whereNull('lecturer_activated_at')
                    ->update(['lecturer_activated_at' => now()]);
            }
        }
        if (! Schema::hasColumn('users', 'institution')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('institution')->nullable();
            });
        }

        foreach (['is_admin', 'lecturer_activated_at'] as $column) {
            if (! Schema::hasIndex('users', [$column])) {
                Schema::table('users', fn (Blueprint $table) => $table->index($column));
            }
        }

        if (! $codesExist) {
            Schema::create('lecturer_activation_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('created_by')->nullable();
                $table->char('code_hash', 64);
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
            });
        }

        // Do not recreate an existing table or invent values for unknown schemas.
        if (! Schema::hasColumns('lecturer_activation_codes', [
            'id', 'user_id', 'created_by', 'code_hash', 'expires_at', 'used_at',
            'revoked_at', 'created_at', 'updated_at',
        ])) {
            throw new RuntimeException('Struktur lecturer_activation_codes tidak sesuai. Cadangkan database dan periksa struktur tabel; jangan hapus data.');
        }

        // A failed CREATE migration may have left the table without its constraints.
        if (! Schema::hasIndex('lecturer_activation_codes', ['code_hash'], 'unique')) {
            Schema::table('lecturer_activation_codes', fn (Blueprint $table) => $table->unique('code_hash'));
        }
        if (! Schema::hasIndex('lecturer_activation_codes', ['user_id', 'used_at', 'revoked_at'])) {
            Schema::table('lecturer_activation_codes', fn (Blueprint $table) => $table->index(['user_id', 'used_at', 'revoked_at']));
        }
        if (! Schema::hasForeignKey('lecturer_activation_codes', ['user_id'])) {
            Schema::table('lecturer_activation_codes', fn (Blueprint $table) => $table
                ->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        }
        if (! Schema::hasForeignKey('lecturer_activation_codes', ['created_by'])) {
            Schema::table('lecturer_activation_codes', fn (Blueprint $table) => $table
                ->foreign('created_by')->references('id')->on('users')->nullOnDelete());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_activation_codes');
        foreach (['is_admin', 'lecturer_activated_at'] as $column) {
            if (Schema::hasIndex('users', 'users_'.$column.'_index')) {
                Schema::table('users', fn (Blueprint $table) => $table->dropIndex([$column]));
            }
        }
        foreach (['is_admin', 'lecturer_activated_at', 'institution'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
