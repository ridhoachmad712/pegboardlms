<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\User;
use App\Services\LecturerAccount;
use App\Services\LecturerActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LecturerAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false]);
    }

    private function admin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->withSession(['lecturer_session_version' => 0]);

        return $admin;
    }

    private function course(User $lecturer): Course
    {
        return Course::create([
            'user_id' => $lecturer->id, 'name' => 'Kelas tetap tersimpan', 'code' => 'ACC101',
            'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
    }

    private function reset(User $lecturer): string
    {
        $this->post(route('admin.lecturers.resetPassword', $lecturer))
            ->assertRedirect(route('admin.lecturers.show', $lecturer));

        return Crypt::decryptString(session('lecturer_password_reset.encrypted_password'));
    }

    private function freshLogin(User $user, string $password, bool $remember = false)
    {
        // Simulate a new device rather than carrying the test guard's cached user.
        Auth::forgetGuards();
        session()->invalidate();

        return $this->post(route('login'), ['email' => $user->email, 'password' => $password, 'remember' => $remember]);
    }

    private function changePayload(string $temporary, string $new = 'Password-pribadi-2026'): array
    {
        return ['current_password' => $temporary, 'password' => $new, 'password_confirmation' => $new];
    }

    public function test_only_admin_can_use_account_controls(): void
    {
        $lecturer = User::factory()->activeLecturer()->create();
        foreach (['activate', 'disable', 'enable', 'resetPassword'] as $action) {
            $method = $action === 'resetPassword' ? 'post' : 'patch';
            $this->$method(route('admin.lecturers.'.$action, $lecturer))->assertRedirect(route('login'));
        }
        foreach ([User::factory()->create(), User::factory()->activeLecturer()->create()] as $user) {
            $this->actingAs($user);
            $this->patch(route('admin.lecturers.activate', $lecturer))->assertForbidden();
            $this->patch(route('admin.lecturers.disable', $lecturer))->assertForbidden();
            $this->patch(route('admin.lecturers.enable', $lecturer))->assertForbidden();
            $this->post(route('admin.lecturers.resetPassword', $lecturer))->assertForbidden();
        }
        $this->assertFalse($lecturer->fresh()->isLecturerDisabled());
        $this->assertTrue(Hash::check('password', $lecturer->fresh()->password));
    }

    public function test_pending_or_disabled_admin_cannot_change_lecturer_accounts(): void
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $pending = User::factory()->admin()->create(['lecturer_activated_at' => null]);
        $this->actingAs($pending)->post(route('admin.lecturers.resetPassword', $lecturer))->assertRedirect(route('activation.show'));
        $disabled = User::factory()->admin()->create(['lecturer_disabled_at' => now()]);
        $this->actingAs($disabled)->patch(route('admin.lecturers.disable', $lecturer))->assertRedirect(route('login'));
        $this->assertFalse($lecturer->fresh()->isLecturerDisabled());
        $this->assertSame(0, $lecturer->fresh()->lecturer_session_version);
        $this->assertTrue(Hash::check('password', $lecturer->fresh()->password));
    }

    public function test_admins_and_students_cannot_be_targets_of_lecturer_controls(): void
    {
        $admin = $this->admin();
        $otherAdmin = User::factory()->admin()->create();
        $student = User::factory()->create();
        foreach ([$admin, $otherAdmin, $student] as $target) {
            $expected = $target->isAdmin() ? 403 : 404;
            $this->patch(route('admin.lecturers.disable', $target))->assertStatus($expected);
            $this->patch(route('admin.lecturers.enable', $target))->assertStatus($expected);
            $this->post(route('admin.lecturers.resetPassword', $target))->assertStatus($expected);
            $this->assertNull($target->fresh()->lecturer_disabled_at);
            $this->assertTrue(Hash::check('password', $target->fresh()->password));
        }
        $this->get(route('admin.lecturers.show', $admin))->assertOk()
            ->assertDontSee('Keamanan akun')->assertDontSee('Nonaktifkan Akun')->assertDontSee('Reset Password');
    }

    public function test_disable_preserves_academic_data_payment_and_student_access(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $course = $this->course($lecturer);
        $student = User::factory()->create();
        $course->students()->attach($student, ['enrolled_at' => now()]);
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Materi uji', 'date' => today()]);
        $material = $meeting->materials()->create(['title' => 'Tetap ada', 'type' => 'link', 'url' => 'https://example.test/materi']);

        $this->patch(route('admin.lecturers.disable', $lecturer))->assertRedirect(route('admin.lecturers.show', $lecturer));
        $disabled = $lecturer->fresh();
        $this->assertTrue($disabled->isLecturerDisabled());
        $this->assertTrue($disabled->lecturer_activated_at->equalTo($lecturer->lecturer_activated_at));
        $this->assertSame($lecturer->password, $disabled->password);
        $this->assertNotSame($lecturer->remember_token, $disabled->remember_token);
        $this->assertSame(1, $disabled->lecturer_session_version);
        $this->assertNotNull($material->fresh());
        $this->assertSame(1, $course->students()->count());
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'lecturer_disabled']);
        $this->actingAs($student)->get(route('courses.show', $course))->assertOk();
        $this->get(route('materials.preview', $material))->assertRedirect('https://example.test/materi');
    }

    public function test_pending_codes_are_revoked_and_cannot_be_issued_while_disabled(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $issued = app(LecturerActivation::class)->issue($lecturer, $admin);
        $this->patch(route('admin.lecturers.disable', $lecturer))->assertRedirect();
        $this->assertNotNull($issued['code']->fresh()->revoked_at);
        $this->post(route('admin.lecturers.issueCode', $lecturer), ['payment_confirmed' => 1])->assertSessionHasErrors('activation');
        $this->get(route('admin.lecturers.show', $lecturer))->assertOk()->assertSee('Dosen tidak dapat login')
            ->assertSee('Aktifkan Kembali Akun')->assertDontSee('name="payment_confirmed"', false);
        $this->expectException(ValidationException::class);
        app(LecturerActivation::class)->redeem($lecturer->fresh(), $issued['plain']);
    }

    public function test_enable_is_idempotent_and_preserves_paid_activation(): void
    {
        $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $this->patch(route('admin.lecturers.disable', $lecturer))->assertRedirect();
        $this->patch(route('admin.lecturers.disable', $lecturer))->assertRedirect();
        $this->assertSame(1, $lecturer->fresh()->lecturer_session_version);
        $this->patch(route('admin.lecturers.enable', $lecturer))->assertRedirect();
        $this->patch(route('admin.lecturers.enable', $lecturer))->assertRedirect();
        $enabled = $lecturer->fresh();
        $this->assertFalse($enabled->isLecturerDisabled());
        $this->assertSame(2, $enabled->lecturer_session_version);
        $this->assertTrue($enabled->lecturer_activated_at->equalTo($lecturer->lecturer_activated_at));
        $this->assertDatabaseCount('activity_logs', 2);
        $this->freshLogin($enabled, 'password')->assertRedirect(route('dashboard'));
        $this->get(route('dashboard.dosen'))->assertOk();
    }

    public function test_reenabled_unpaid_lecturer_still_needs_payment_activation(): void
    {
        $this->admin();
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $this->patch(route('admin.lecturers.disable', $lecturer))->assertRedirect();
        $this->patch(route('admin.lecturers.enable', $lecturer))->assertRedirect();
        $this->assertTrue($lecturer->fresh()->needsLecturerActivation());
        $this->freshLogin($lecturer, 'password')->assertRedirect(route('activation.show'));
        $this->get(route('dashboard'))->assertRedirect(route('activation.show'));
    }

    public function test_admin_activates_pending_lecturer_directly_without_any_code(): void
    {
        $this->admin();
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $this->assertTrue($lecturer->needsLecturerActivation());

        // Halaman detail kini menampilkan aktivasi langsung, bukan penerbitan kode.
        $this->get(route('admin.lecturers.show', $lecturer))->assertOk()
            ->assertSee('Aktifkan Akun Dosen')->assertDontSee('name="payment_confirmed"', false);

        $this->patch(route('admin.lecturers.activate', $lecturer))
            ->assertRedirect(route('admin.lecturers.show', $lecturer));

        $activated = $lecturer->fresh();
        $this->assertFalse($activated->needsLecturerActivation());
        $this->assertNotNull($activated->lecturer_activated_at);
        $this->assertDatabaseCount('lecturer_activation_codes', 0);
        $this->assertSame(1, ActivityLog::where('action', 'lecturer_activation')->count());

        // Dosen langsung dapat masuk tanpa kode aktivasi.
        $this->freshLogin($activated, 'password')->assertRedirect(route('dashboard'));
        $this->get(route('dashboard.dosen'))->assertOk();
    }

    public function test_activate_is_idempotent_and_does_not_move_activation_time(): void
    {
        $this->admin();
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $this->patch(route('admin.lecturers.activate', $lecturer))->assertRedirect();
        $firstActivatedAt = $lecturer->fresh()->lecturer_activated_at;

        $this->travel(1)->hours();
        $this->patch(route('admin.lecturers.activate', $lecturer))->assertRedirect();
        $this->assertTrue($lecturer->fresh()->lecturer_activated_at->equalTo($firstActivatedAt));
        $this->assertSame(1, ActivityLog::where('action', 'lecturer_activation')->count());
    }

    public function test_disabled_login_is_rejected_even_with_correct_password(): void
    {
        $lecturer = User::factory()->activeLecturer()->create(['lecturer_disabled_at' => now()]);
        $this->freshLogin($lecturer, 'password', true)->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull(session('lecturer_session_version'));
    }

    public function test_disabled_sessions_are_blocked_on_reads_writes_downloads_and_json(): void
    {
        $lecturer = User::factory()->activeLecturer()->create(['lecturer_disabled_at' => now(), 'lecturer_session_version' => 1]);
        $course = $this->course($lecturer);
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Uji', 'date' => today()]);
        $material = $meeting->materials()->create(['title' => 'Materi', 'type' => 'text', 'content' => 'Isi']);
        foreach ([route('dashboard'), route('courses.show', $course), route('materials.download', $material),
            route('profile.edit'), route('activation.show'), route('lecturer.password.edit')] as $url) {
            $this->actingAs($lecturer)->withSession(['lecturer_session_version' => 0])->get($url)->assertRedirect(route('login'));
            $this->assertGuest();
        }
        $this->actingAs($lecturer)->put(route('courses.update', $course), ['name' => 'Tidak boleh'])->assertRedirect(route('login'));
        $this->actingAs($lecturer)->getJson(route('analytics.data', $course))->assertForbidden();
        $this->assertSame('Kelas tetap tersimpan', $course->fresh()->name);
    }

    public function test_reset_generates_one_time_secret_and_revokes_old_password_and_sessions(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $plain = $this->reset($lecturer);
        $fresh = $lecturer->fresh();
        $this->assertMatchesRegularExpression('/\A[A-Za-z0-9]{20}\z/', $plain);
        $this->assertMatchesRegularExpression('/[A-Za-z]/', $plain);
        $this->assertMatchesRegularExpression('/[0-9]/', $plain);
        $this->assertTrue(Hash::check($plain, $fresh->password));
        $this->assertFalse(Hash::check('password', $fresh->password));
        $this->assertTrue($fresh->must_change_password);
        $this->assertSame(1, $fresh->lecturer_session_version);
        $this->assertNotSame($lecturer->remember_token, $fresh->remember_token);
        $this->assertTrue($fresh->lecturer_activated_at->equalTo($lecturer->lecturer_activated_at));
        $this->assertStringNotContainsString($plain, json_encode(DB::table('users')->get()));
        $this->assertStringNotContainsString($plain, json_encode(DB::table('activity_logs')->get()));
        $this->assertStringNotContainsString($plain, json_encode(session()->all()));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'action' => 'lecturer_password_reset']);
        $this->get(route('admin.lecturers.show', $lecturer))->assertOk()->assertSee($plain)
            ->assertHeader('Cache-Control', 'no-store, private')->assertSee('Password hanya ditampilkan kali ini.');
        $this->get(route('admin.lecturers.show', $lecturer))->assertOk()->assertDontSee($plain);
    }

    public function test_reset_secret_is_not_shown_on_another_lecturer_detail(): void
    {
        $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $plain = $this->reset($lecturer);
        $this->get(route('admin.lecturers.show', User::factory()->activeLecturer()->create()))->assertOk()->assertDontSee($plain);
        $this->get(route('admin.lecturers.show', $lecturer))->assertOk()->assertDontSee($plain);
    }

    public function test_reset_does_not_enable_disabled_account_or_bypass_payment(): void
    {
        $this->admin();
        $lecturer = User::factory()->create(['role' => 'dosen', 'lecturer_disabled_at' => now()]);
        $plain = $this->reset($lecturer);
        $this->assertTrue($lecturer->fresh()->isLecturerDisabled());
        $this->assertTrue($lecturer->fresh()->needsLecturerActivation());
        $this->get(route('admin.lecturers.show', $lecturer))->assertOk()->assertSee('Akun masih nonaktif.');
        $this->patch(route('admin.lecturers.enable', $lecturer))->assertRedirect();
        $this->assertTrue($lecturer->fresh()->must_change_password);
        $this->freshLogin($lecturer, $plain)->assertRedirect(route('lecturer.password.edit'));
        $this->put(route('lecturer.password.update'), $this->changePayload($plain))->assertRedirect(route('dashboard'));
        $this->actingAs($lecturer->fresh())->get(route('dashboard'))->assertRedirect(route('activation.show'));
        $this->get(route('activation.show'))->assertOk();
    }

    public function test_second_reset_invalidates_first_temporary_password(): void
    {
        $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $first = $this->reset($lecturer);
        $second = $this->reset($lecturer);
        $this->assertNotSame($first, $second);
        $this->assertSame(2, $lecturer->fresh()->lecturer_session_version);
        $this->freshLogin($lecturer, $first)->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->freshLogin($lecturer, $second)->assertRedirect(route('lecturer.password.edit'));
    }

    public function test_stale_sessions_and_sessions_without_version_are_rejected_after_reset(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        app(LecturerAccount::class)->resetPassword($lecturer, $admin);
        $fresh = $lecturer->fresh();
        $this->actingAs($fresh)->withSession(['lecturer_session_version' => 0])->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($fresh)->getJson(route('notifications.index'))->assertUnauthorized();
        $this->assertGuest();
        $this->assertSame($fresh->remember_token, $lecturer->fresh()->remember_token);
    }

    public function test_reenabling_never_revives_a_session_from_before_disabling(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $service = app(LecturerAccount::class);
        $service->setDisabled($lecturer, $admin, true);
        $service->setDisabled($lecturer, $admin, false);
        $this->actingAs($lecturer->fresh())->withSession(['lecturer_session_version' => 0])
            ->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_legacy_sessions_before_any_security_action_keep_working(): void
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $this->actingAs($lecturer)->get(route('dashboard.dosen'))->assertOk()->assertSessionHas('lecturer_session_version', 0);
    }

    public function test_valid_remember_login_establishes_current_version_but_old_cookie_is_rejected(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $oldToken = $lecturer->remember_token;
        app(LecturerAccount::class)->resetPassword($lecturer, $admin);
        $fresh = $lecturer->fresh();
        $recaller = Auth::guard('web')->getRecallerName();
        Auth::forgetGuards();
        session()->invalidate();
        $this->withCookie($recaller, $fresh->id.'|'.$oldToken.'|'.$lecturer->password)
            ->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        Auth::forgetGuards();
        session()->invalidate();
        $this->withCookie($recaller, $fresh->id.'|'.$fresh->remember_token.'|'.$fresh->password)
            ->get(route('dashboard'))->assertRedirect(route('lecturer.password.edit'))
            ->assertSessionHas('lecturer_session_version', 1);
        $this->assertAuthenticatedAs($fresh);
    }

    public function test_temporary_login_cannot_bypass_password_change_with_intended_url_or_direct_routes(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $course = $this->course($lecturer);
        $plain = app(LecturerAccount::class)->resetPassword($lecturer, $admin);
        $this->freshLogin($lecturer, $plain)->assertRedirect(route('lecturer.password.edit'));
        $this->get(route('lecturer.password.edit'))->assertOk()->assertSee('Ganti password sementara')
            ->assertHeader('Cache-Control', 'no-store, private');
        foreach ([route('dashboard'), route('profile.edit'), route('courses.show', $course), route('activation.show')] as $url) {
            $this->get($url)->assertRedirect(route('lecturer.password.edit'));
        }
        $this->put(route('courses.update', $course), ['name' => 'Tidak boleh'])->assertRedirect(route('lecturer.password.edit'));
        $this->getJson(route('analytics.data', $course))->assertForbidden()->assertJson(['password_change_required' => true]);
        $this->assertSame('Kelas tetap tersimpan', $course->fresh()->name);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_change_rejects_incorrect_current_same_short_and_unconfirmed_passwords(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $plain = app(LecturerAccount::class)->resetPassword($lecturer, $admin);
        $this->freshLogin($lecturer, $plain)->assertRedirect(route('lecturer.password.edit'));
        $cases = [
            [$this->changePayload('password-lama-salah'), 'current_password'],
            [$this->changePayload($plain, $plain), 'password'],
            [$this->changePayload($plain, 'pendek'), 'password'],
            [array_merge($this->changePayload($plain), ['password_confirmation' => 'tidak-sama']), 'password'],
            [$this->changePayload($plain, str_repeat('é', 40)), 'password'],
        ];
        foreach ($cases as [$payload, $field]) {
            $this->from(route('lecturer.password.edit'))->put(route('lecturer.password.update'), $payload)
                ->assertSessionHasErrors($field);
            $this->assertNull(session('_old_input.current_password'));
            $this->assertNull(session('_old_input.password'));
            $this->assertNull(session('_old_input.password_confirmation'));
            $this->assertTrue(Hash::check($plain, $lecturer->fresh()->password));
            $this->assertTrue($lecturer->fresh()->must_change_password);
        }
    }

    public function test_password_change_clears_requirement_rotates_session_and_invalidates_temporary_password(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        $plain = app(LecturerAccount::class)->resetPassword($lecturer, $admin);
        $this->freshLogin($lecturer, $plain)->assertRedirect(route('lecturer.password.edit'));
        $oldId = session()->getId();
        $this->put(route('lecturer.password.update'), $this->changePayload($plain))
            ->assertRedirect(route('dashboard'))->assertSessionHas('lecturer_session_version', 2);
        $this->assertNotSame($oldId, session()->getId());
        $fresh = $lecturer->fresh();
        $this->assertFalse($fresh->must_change_password);
        $this->assertTrue(Hash::check('Password-pribadi-2026', $fresh->password));
        $this->assertFalse(Hash::check($plain, $fresh->password));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $lecturer->id, 'action' => 'lecturer_password_changed']);
        $this->actingAs($fresh)->get(route('dashboard.dosen'))->assertOk();
        $this->get(route('lecturer.password.edit'))->assertRedirect(route('dashboard'));
        $this->withSession(['lecturer_session_version' => 1])->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->freshLogin($fresh, $plain)->assertSessionHasErrors('email');
        $this->freshLogin($fresh, 'Password-pribadi-2026')->assertRedirect(route('dashboard'));
    }

    public function test_accounts_without_temporary_password_cannot_use_forced_reset_endpoint(): void
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $this->actingAs($lecturer)->get(route('lecturer.password.edit'))->assertRedirect(route('dashboard'));
        $this->put(route('lecturer.password.update'), $this->changePayload('password'))->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $lecturer->fresh()->password));
        $this->actingAs(User::factory()->create())->get(route('lecturer.password.edit'))->assertForbidden();
        $this->put(route('lecturer.password.update'), $this->changePayload('password'))->assertForbidden();
    }

    public function test_admin_counts_and_filters_separate_disabled_from_pending_and_active(): void
    {
        $this->admin();
        User::factory()->activeLecturer()->create();
        User::factory()->create(['role' => 'dosen']);
        User::factory()->activeLecturer()->create(['lecturer_disabled_at' => now()]);
        $disabledPending = User::factory()->create(['role' => 'dosen', 'lecturer_disabled_at' => now(), 'name' => 'Nonaktif Uji']);
        foreach (['pending' => 1, 'active' => 1, 'disabled' => 2, 'all' => 4] as $status => $count) {
            $this->get(route('admin.lecturers.index', ['status' => $status]))->assertOk()
                ->assertViewHas('pendingCount', 1)->assertViewHas('activeCount', 1)->assertViewHas('disabledCount', 2)
                ->assertViewHas('lecturers', fn ($items) => $items->total() === $count);
        }
        $this->get(route('admin.lecturers.index', ['status' => 'disabled', 'q' => 'Nonaktif Uji']))->assertOk()
            ->assertViewHas('lecturers', fn ($items) => $items->total() === 1 && $items->first()->is($disabledPending));
        $this->get(route('admin.dashboard'))->assertOk()->assertViewHas('pendingCount', 1)->assertViewHas('activeCount', 1);
    }

    public function test_account_security_fields_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->activeLecturer()->create();
        $user->fill(['lecturer_disabled_at' => now(), 'must_change_password' => true, 'lecturer_session_version' => 999])->save();
        $this->assertNull($user->fresh()->lecturer_disabled_at);
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertSame(0, $user->fresh()->lecturer_session_version);
    }

    public function test_migration_resumes_without_overwriting_accounts(): void
    {
        $migration = require database_path('migrations/2026_08_27_100000_add_lecturer_account_controls.php');
        $migration->down();
        $lecturer = User::factory()->activeLecturer()->create();
        $migration->up();
        $this->assertNull($lecturer->fresh()->lecturer_disabled_at);
        $this->assertFalse($lecturer->fresh()->must_change_password);
        $this->assertSame(0, $lecturer->fresh()->lecturer_session_version);
        $lecturer->forceFill(['lecturer_disabled_at' => now(), 'lecturer_session_version' => 2])->save();
        $migration->up();
        $this->assertTrue($lecturer->fresh()->isLecturerDisabled());
        $this->assertSame(2, $lecturer->fresh()->lecturer_session_version);
        $this->assertTrue(Hash::check('password', $lecturer->fresh()->password));
        $this->assertTrue(Schema::hasIndex('users', ['lecturer_disabled_at']));
    }

    public function test_failed_audit_rolls_back_account_and_code_changes(): void
    {
        $admin = $this->admin();
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $issued = app(LecturerActivation::class)->issue($lecturer, $admin);
        ActivityLog::creating(fn () => throw new \RuntimeException('Audit unavailable'));
        try {
            foreach (['disable', 'reset'] as $action) {
                try {
                    $service = app(LecturerAccount::class);
                    $action === 'disable' ? $service->setDisabled($lecturer, $admin, true) : $service->resetPassword($lecturer, $admin);
                    $this->fail('Expected failed audit');
                } catch (\RuntimeException $exception) {
                    $this->assertSame('Audit unavailable', $exception->getMessage());
                }
                $fresh = $lecturer->fresh();
                $this->assertFalse($fresh->isLecturerDisabled());
                $this->assertFalse($fresh->must_change_password);
                $this->assertSame(0, $fresh->lecturer_session_version);
                $this->assertSame($lecturer->password, $fresh->password);
                $this->assertNull($issued['code']->fresh()->revoked_at);
            }
        } finally {
            ActivityLog::flushEventListeners();
        }
    }

    public function test_reset_attempts_are_rate_limited(): void
    {
        $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.lecturers.resetPassword', $lecturer))->assertRedirect();
        }
        $this->post(route('admin.lecturers.resetPassword', $lecturer))->assertStatus(429);
        $this->assertSame(5, $lecturer->fresh()->lecturer_session_version);
    }

    public function test_reset_rate_limit_does_not_consume_access_or_activation_limits(): void
    {
        $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.lecturers.resetPassword', $lecturer))->assertRedirect();
        }
        $this->post(route('admin.lecturers.resetPassword', $lecturer))->assertStatus(429);
        $this->patch(route('admin.lecturers.disable', $lecturer))->assertRedirect();
        $this->patch(route('admin.lecturers.enable', $lecturer))->assertRedirect();
        $pending = User::factory()->create(['role' => 'dosen']);
        $this->post(route('admin.lecturers.issueCode', $pending), ['payment_confirmed' => 1])->assertRedirect();
        $this->assertFalse($lecturer->fresh()->isLecturerDisabled());
        $this->assertDatabaseCount('lecturer_activation_codes', 1);
    }

    public function test_demo_blocks_account_controls(): void
    {
        $this->admin();
        $lecturer = User::factory()->activeLecturer()->create();
        config(['demo.enabled' => true]);
        $this->patch(route('admin.lecturers.disable', $lecturer))->assertSessionHas('error');
        $this->patch(route('admin.lecturers.enable', $lecturer))->assertSessionHas('error');
        $this->post(route('admin.lecturers.resetPassword', $lecturer))->assertSessionHas('error');
        $this->assertFalse($lecturer->fresh()->isLecturerDisabled());
        $this->assertTrue(Hash::check('password', $lecturer->fresh()->password));
    }
}
