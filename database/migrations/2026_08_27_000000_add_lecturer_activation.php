<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $reference = $this->userReferenceDefinition();
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
            Schema::create('lecturer_activation_codes', function (Blueprint $table) use ($reference) {
                $table->id();
                $type = $reference['blueprint_type'];
                $table->$type('user_id')->unsigned($reference['unsigned']);
                $table->$type('created_by')->unsigned($reference['unsigned'])->nullable();
                $table->engine('InnoDB');
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

        $this->alignUserReferences($reference);

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

    private function userReferenceDefinition(): array
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return ['mysql' => false, 'blueprint_type' => 'bigInteger', 'unsigned' => true];
        }

        $this->requireInnoDb('users');
        $column = collect(Schema::getColumns('users'))->firstWhere('name', 'id');
        $type = strtolower($column['type_name'] ?? '');
        $blueprintType = match ($type) {
            'bigint' => 'bigInteger',
            'int', 'integer' => 'integer',
            'mediumint' => 'mediumInteger',
            'smallint' => 'smallInteger',
            'tinyint' => 'tinyInteger',
            default => throw new RuntimeException('Tipe users.id bukan integer yang didukung. Periksa struktur tabel; jangan mengubah ID pengguna secara massal.'),
        };

        return [
            'mysql' => true, 'blueprint_type' => $blueprintType, 'type_name' => $type,
            'unsigned' => str_contains(strtolower($column['type']), 'unsigned'),
        ];
    }

    private function requireInnoDb(string $table): void
    {
        $connection = DB::connection();
        $info = $connection->selectOne(
            'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$connection->getDatabaseName(), $connection->getTablePrefix().$table]
        );
        if (strtolower($info->engine ?? '') !== 'innodb') {
            throw new RuntimeException('Tabel '.$table.' harus menggunakan InnoDB untuk foreign key. Cadangkan database dan periksa engine tabel; migrasi tidak mengonversi tabel lama otomatis.');
        }
    }

    private function alignUserReferences(array $reference): void
    {
        if (! $reference['mysql']) {
            return;
        }

        $this->requireInnoDb('lecturer_activation_codes');
        $columns = collect(Schema::getColumns('lecturer_activation_codes'))->keyBy('name');
        $changes = [];
        foreach (['user_id' => false, 'created_by' => true] as $name => $nullable) {
            $column = $columns[$name];
            // Check both references before changing either. Matching an existing user
            // also proves the value fits the parent's integer range without truncation.
            $orphans = DB::table('lecturer_activation_codes as codes')->whereNotNull('codes.'.$name)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('users')
                    ->whereColumn('users.id', 'codes.'.$name))->exists();
            $invalidNull = ! $nullable && DB::table('lecturer_activation_codes')->whereNull($name)->exists();
            if ($orphans || $invalidNull) {
                throw new RuntimeException('Referensi '.$name.' pada lecturer_activation_codes tidak cocok dengan users.id. Data dipertahankan; periksa referensi sebelum melanjutkan migrasi.');
            }

            $unsigned = str_contains(strtolower($column['type']), 'unsigned');
            if (strtolower($column['type_name']) !== $reference['type_name'] || $unsigned !== $reference['unsigned']) {
                if (Schema::hasForeignKey('lecturer_activation_codes', [$name])) {
                    throw new RuntimeException('Kolom '.$name.' sudah memiliki foreign key dengan tipe berbeda. Periksa struktur tabel; relasi tidak dilepas otomatis.');
                }
                $changes[$name] = $nullable;
            }
        }

        // Only fix the two references in the new table, never users.id or other tables.
        foreach ($changes as $name => $nullable) {
            Schema::table('lecturer_activation_codes', function (Blueprint $table) use ($reference, $name, $nullable) {
                $type = $reference['blueprint_type'];
                $table->$type($name)->unsigned($reference['unsigned'])->nullable($nullable)->change();
            });
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
