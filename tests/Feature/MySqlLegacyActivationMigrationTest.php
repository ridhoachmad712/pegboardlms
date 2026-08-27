<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MySqlLegacyActivationMigrationTest extends TestCase
{
    private ?string $originalConnection = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Integer signedness and foreign key compatibility require MySQL/MariaDB.');
        }
        $this->originalConnection = DB::getDefaultConnection();
        $config = DB::connection()->getConfig();
        $config['prefix'] = 'q'.bin2hex(random_bytes(2)).'_';
        config(['database.connections.activation_legacy_test' => $config]);
        DB::setDefaultConnection('activation_legacy_test');
        Schema::clearResolvedInstance('db.schema');
    }

    protected function tearDown(): void
    {
        if ($this->originalConnection !== null) {
            try {
                // Only the two randomly-prefixed fixture tables belong to this test.
                Schema::dropIfExists('lecturer_activation_codes');
                Schema::dropIfExists('users');
            } finally {
                DB::purge('activation_legacy_test');
                DB::setDefaultConnection($this->originalConnection);
                Schema::clearResolvedInstance('db.schema');
            }
        }
        parent::tearDown();
    }

    private function createUsers(string $type = 'bigInteger', bool $unsigned = false, string $engine = 'InnoDB'): void
    {
        Schema::create('users', function (Blueprint $table) use ($type, $unsigned, $engine) {
            $table->engine($engine);
            $table->$type('id')->unsigned($unsigned)->autoIncrement();
            $table->string('role');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('lecturer_activated_at')->nullable();
            $table->string('institution')->nullable();
        });
        DB::table('users')->insert([
            ['id' => 1, 'role' => 'dosen', 'is_admin' => false, 'lecturer_activated_at' => null, 'institution' => 'Fixture pending'],
            ['id' => 2, 'role' => 'dosen', 'is_admin' => true, 'lecturer_activated_at' => '2026-01-01 00:00:00', 'institution' => 'Fixture admin'],
        ]);
    }

    private function createPartialCodeTable(bool $userUnsigned = true): void
    {
        Schema::create('lecturer_activation_codes', function (Blueprint $table) use ($userUnsigned) {
            $table->engine('InnoDB');
            $table->id();
            $table->bigInteger('user_id')->unsigned($userUnsigned);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->char('code_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    private function insertCode(array $overrides = []): void
    {
        DB::table('lecturer_activation_codes')->insert(array_merge([
            'user_id' => 1, 'created_by' => 2, 'code_hash' => hash('sha256', 'fixture'),
            'expires_at' => '2026-08-01 00:00:00', 'used_at' => '2026-07-30 00:00:00',
            'revoked_at' => null, 'created_at' => '2026-07-29 00:00:00', 'updated_at' => '2026-07-30 00:00:00',
        ], $overrides));
    }

    private function migrateActivation(): void
    {
        (require database_path('migrations/2026_08_27_000000_add_lecturer_activation.php'))->up();
    }

    public static function idTypes(): array
    {
        return [
            'legacy signed bigint' => ['bigInteger', false],
            'native unsigned bigint' => ['bigInteger', true],
            'legacy signed int' => ['integer', false],
            'legacy unsigned int' => ['integer', true],
        ];
    }

    #[DataProvider('idTypes')]
    public function test_new_code_table_uses_actual_parent_id_type(string $type, bool $unsigned): void
    {
        $this->createUsers($type, $unsigned);
        $before = DB::table('users')->orderBy('id')->get();
        $this->migrateActivation();
        $this->assertReferencesMatch();
        $this->insertCode();
        $this->migrateActivation();
        $this->assertEquals($before, DB::table('users')->orderBy('id')->get());
        $this->assertSame(1, DB::table('lecturer_activation_codes')->count());
    }

    public function test_reproduces_hosting_error_then_repairs_partial_unsigned_references_without_data_loss(): void
    {
        $this->createUsers();
        $this->createPartialCodeTable();
        $this->insertCode();
        try {
            Schema::table('lecturer_activation_codes', fn (Blueprint $table) => $table
                ->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
            $this->fail('Signed parent and unsigned child must reproduce the hosting failure.');
        } catch (QueryException $error) {
            $this->assertContains((int) $error->errorInfo[1], [1005, 3780]);
        }
        $beforeUsers = DB::table('users')->orderBy('id')->get();
        $beforeCodes = DB::table('lecturer_activation_codes')->get();
        $this->migrateActivation();
        $this->migrateActivation();
        $this->assertReferencesMatch();
        $this->assertEquals($beforeUsers, DB::table('users')->orderBy('id')->get());
        $this->assertEquals($beforeCodes, DB::table('lecturer_activation_codes')->get());

        DB::table('users')->where('id', 2)->delete();
        $this->assertNull(DB::table('lecturer_activation_codes')->value('created_by'));
        DB::table('users')->where('id', 1)->delete();
        $this->assertSame(0, DB::table('lecturer_activation_codes')->count());
    }

    private function assertReferencesMatch(): void
    {
        $parentType = Schema::getColumnType('users', 'id', true);
        foreach (['user_id', 'created_by'] as $name) {
            $this->assertSame($parentType, Schema::getColumnType('lecturer_activation_codes', $name, true));
            $this->assertTrue(Schema::hasForeignKey('lecturer_activation_codes', [$name]));
        }
    }

    public static function invalidReferences(): array
    {
        return [
            'unknown user' => ['user_id', '999'],
            'unknown creator' => ['created_by', '999'],
            'unsigned value outside signed range' => ['user_id', '18446744073709551615'],
        ];
    }

    #[DataProvider('invalidReferences')]
    public function test_invalid_references_stop_before_either_column_is_converted(string $column, string $value): void
    {
        $this->createUsers();
        $this->createPartialCodeTable();
        $this->insertCode([$column => $value]);
        $before = DB::table('lecturer_activation_codes')->get();
        try {
            $this->migrateActivation();
            $this->fail('Unsafe conversion must be refused.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('Referensi '.$column, $error->getMessage());
        }
        $this->assertEquals($before, DB::table('lecturer_activation_codes')->get());
        foreach (['user_id', 'created_by'] as $name) {
            $this->assertStringContainsString('unsigned', Schema::getColumnType('lecturer_activation_codes', $name, true));
        }
    }

    public function test_existing_correct_foreign_key_is_retained_when_repairing_the_other_reference(): void
    {
        $this->createUsers();
        $this->createPartialCodeTable(userUnsigned: false);
        $this->insertCode();
        Schema::table('lecturer_activation_codes', fn (Blueprint $table) => $table
            ->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        $this->migrateActivation();
        $this->assertReferencesMatch();
        $this->assertSame(1, DB::table('lecturer_activation_codes')->count());
    }

    public function test_non_innodb_parent_is_not_converted_automatically(): void
    {
        $this->createUsers(engine: 'MyISAM');
        $before = DB::table('users')->orderBy('id')->get();
        try {
            $this->migrateActivation();
            $this->fail('Parent engine conversion must not be implicit.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('InnoDB', $error->getMessage());
        }
        $this->assertEquals($before, DB::table('users')->orderBy('id')->get());
        $this->assertFalse(Schema::hasTable('lecturer_activation_codes'));
    }
}
