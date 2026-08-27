<?php

namespace Tests\Feature;

use App\Models\LecturerActivationCode;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LecturerActivationMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_08_27_000000_add_lecturer_activation';

    private function migration(): Migration
    {
        return require database_path('migrations/'.self::MIGRATION.'.php');
    }

    private function assertCompleteSchema(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['is_admin', 'lecturer_activated_at', 'institution']));
        $this->assertTrue(Schema::hasIndex('users', ['is_admin']));
        $this->assertTrue(Schema::hasIndex('users', ['lecturer_activated_at']));
        $this->assertTrue(Schema::hasIndex('lecturer_activation_codes', ['code_hash'], 'unique'));
        $this->assertTrue(Schema::hasIndex('lecturer_activation_codes', ['user_id', 'used_at', 'revoked_at']));
        $this->assertTrue(Schema::hasForeignKey('lecturer_activation_codes', ['user_id']));
        $this->assertTrue(Schema::hasForeignKey('lecturer_activation_codes', ['created_by']));
    }

    public function test_resumes_when_only_is_admin_was_added_and_records_the_migration(): void
    {
        $this->migration()->down();
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        $owner = User::factory()->create(['role' => 'dosen', 'is_admin' => true]);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create();
        DB::table('migrations')->where('migration', self::MIGRATION)->delete();

        $this->artisan('migrate', ['--force' => true, '--path' => 'database/migrations/'.self::MIGRATION.'.php'])
            ->assertSuccessful();
        $this->assertCompleteSchema();
        $this->assertTrue($owner->fresh()->isAdmin());
        $this->assertFalse($lecturer->fresh()->isAdmin());
        $this->assertFalse($lecturer->fresh()->needsLecturerActivation());
        $this->assertNull($student->fresh()->lecturer_activated_at);
        $this->assertDatabaseHas('migrations', ['migration' => self::MIGRATION]);

        $before = DB::table('users')->orderBy('id')->get();
        $this->artisan('migrate', ['--force' => true, '--path' => 'database/migrations/'.self::MIGRATION.'.php'])
            ->assertSuccessful();
        $this->assertEquals($before, DB::table('users')->orderBy('id')->get());
    }

    public function test_resumes_existing_user_columns_without_activating_pending_accounts(): void
    {
        Schema::drop('lecturer_activation_codes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
            $table->dropIndex(['lecturer_activated_at']);
        });
        $active = User::factory()->admin()->create(['institution' => 'Institusi Lama']);
        $pending = User::factory()->create(['role' => 'dosen']);
        User::factory()->create();
        $before = DB::table('users')->orderBy('id')->get();

        $this->migration()->up();
        $this->assertCompleteSchema();
        $this->assertEquals($before, DB::table('users')->orderBy('id')->get());
        $this->assertTrue($pending->fresh()->needsLecturerActivation());
        $this->assertTrue($active->fresh()->isAdmin());
    }

    public function test_resumes_two_existing_columns_without_filling_null_activation_dates(): void
    {
        Schema::drop('lecturer_activation_codes');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('institution'));
        $pending = User::factory()->create(['role' => 'dosen']);

        $this->migration()->up();
        $this->assertCompleteSchema();
        $this->assertTrue($pending->fresh()->needsLecturerActivation());
        $this->assertFalse($pending->fresh()->isAdmin());
    }

    public function test_repairs_existing_code_table_constraints_without_changing_history(): void
    {
        Schema::drop('lecturer_activation_codes');
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
        $admin = User::factory()->admin()->create();
        $pending = User::factory()->create(['role' => 'dosen']);
        $used = LecturerActivationCode::create([
            'user_id' => $pending->id, 'created_by' => $admin->id,
            'code_hash' => hash('sha256', 'synthetic-used'), 'expires_at' => now()->addDay(), 'used_at' => now(),
        ]);
        LecturerActivationCode::create([
            'user_id' => $pending->id, 'created_by' => $admin->id,
            'code_hash' => hash('sha256', 'synthetic-expired'), 'expires_at' => now()->subDay(), 'revoked_at' => now(),
        ]);
        $usersBefore = DB::table('users')->orderBy('id')->get();
        $codesBefore = DB::table('lecturer_activation_codes')->orderBy('id')->get();

        $this->migration()->up();
        $this->assertCompleteSchema();
        $this->assertEquals($usersBefore, DB::table('users')->orderBy('id')->get());
        $this->assertEquals($codesBefore, DB::table('lecturer_activation_codes')->orderBy('id')->get());
        $this->assertFalse($used->fresh()->isUsable());
        $this->assertTrue($pending->fresh()->needsLecturerActivation());

        // Existing and missing constraints may also be mixed on a retry.
        Schema::table('lecturer_activation_codes', fn (Blueprint $table) => $table->dropUnique(['code_hash']));
        $this->migration()->up();
        $this->assertCompleteSchema();
        $this->assertEquals($codesBefore, DB::table('lecturer_activation_codes')->orderBy('id')->get());

        $admin->delete();
        $this->assertNull($used->fresh()->created_by);
        $pending->delete();
        $this->assertDatabaseCount('lecturer_activation_codes', 0);
    }

    public function test_rerunning_completed_migration_preserves_all_account_state(): void
    {
        User::factory()->admin()->create(['lecturer_activated_at' => now()->subYear()]);
        $pending = User::factory()->create(['role' => 'dosen']);
        User::factory()->create();
        $before = DB::table('users')->orderBy('id')->get();
        $indexes = Schema::getIndexes('lecturer_activation_codes');

        $this->migration()->up();
        $this->migration()->up();
        $this->assertCompleteSchema();
        $this->assertEquals($before, DB::table('users')->orderBy('id')->get());
        $this->assertEquals($indexes, Schema::getIndexes('lecturer_activation_codes'));
        $this->assertTrue($pending->fresh()->needsLecturerActivation());
    }

    public function test_existing_code_table_prevents_legacy_backfill_if_activation_column_is_missing(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['lecturer_activated_at']);
            $table->dropColumn('lecturer_activated_at');
        });
        $pending = User::factory()->create(['role' => 'dosen']);

        $this->migration()->up();
        $this->assertCompleteSchema();
        $this->assertTrue($pending->fresh()->needsLecturerActivation());
    }

    public function test_down_can_be_retried_on_an_incomplete_schema(): void
    {
        $this->migration()->down();
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        $this->migration()->down();
        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
        $this->migration()->up();
        $this->assertCompleteSchema();
    }
}
