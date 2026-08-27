<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_active_admin_with_hashed_password_and_working_login(): void
    {
        config(['demo.enabled' => false]);
        $plain = 'Synthetic-test-secret-123';
        $this->artisan('lms:create-admin', ['email' => ' ADMIN@example.test ', '--name' => 'Admin Uji'])
            ->expectsQuestion('Kata sandi admin (12–72 karakter)', $plain)
            ->expectsQuestion('Ulangi kata sandi admin', $plain)
            ->assertSuccessful();

        $user = User::sole();
        $this->assertSame('admin@example.test', $user->email);
        $this->assertSame('Admin Uji', $user->name);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->needsLecturerActivation());
        $this->assertNotSame($plain, $user->password);
        $this->assertTrue(Hash::check($plain, $user->password));
        $this->assertStringNotContainsString($plain, json_encode(DB::table('activity_logs')->get()));
        $this->post(route('login'), ['email' => $user->email, 'password' => $plain])->assertRedirect(route('dashboard'));
        $this->get(route('admin.lecturers.index'))->assertOk();
    }

    public function test_existing_accounts_and_passwords_are_never_overwritten(): void
    {
        foreach (['mahasiswa', 'dosen'] as $role) {
            $user = User::factory()->create(['role' => $role, 'email' => strtoupper($role).'@EXAMPLE.TEST']);
            $before = $user->fresh()->getRawOriginal();
            $this->artisan('lms:create-admin', ['email' => strtolower($user->email), '--name' => 'Tidak Boleh'])
                ->assertFailed();
            $this->assertSame($before, $user->fresh()->getRawOriginal());
        }
        $this->assertDatabaseCount('users', 2);
    }

    public function test_invalid_identity_does_not_create_an_account(): void
    {
        foreach ([['email' => 'invalid', '--name' => 'Admin'], ['email' => 'admin@example.test'],
            ['email' => 'admin@example.test', '--name' => str_repeat('A', 256)]] as $input) {
            $this->artisan('lms:create-admin', $input)->assertFailed();
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_or_mismatched_passwords_do_not_create_an_account(): void
    {
        foreach ([['short', 'short'], ['Synthetic-secret-123', 'different'], [str_repeat('A', 73), str_repeat('A', 73)]] as [$plain, $confirmation]) {
            $this->artisan('lms:create-admin', ['email' => 'admin@example.test', '--name' => 'Admin'])
                ->expectsQuestion('Kata sandi admin (12–72 karakter)', $plain)
                ->expectsQuestion('Ulangi kata sandi admin', $confirmation)
                ->assertFailed();
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_noninteractive_run_cannot_create_an_account_with_an_empty_password(): void
    {
        $this->artisan('lms:create-admin', ['email' => 'admin@example.test', '--name' => 'Admin', '--no-interaction' => true])
            ->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }
}
