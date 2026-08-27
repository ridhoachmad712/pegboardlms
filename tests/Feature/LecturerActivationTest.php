<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LecturerActivationCode;
use App\Models\Notification;
use App\Models\User;
use App\Services\LecturerActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LecturerActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false, 'licensing.code_valid_days' => 7]);
    }

    private function pending(): User
    {
        return User::factory()->create(['role' => User::ROLE_DOSEN]);
    }

    private function course(User $lecturer): Course
    {
        return Course::create([
            'user_id' => $lecturer->id, 'name' => 'Kelas Uji', 'code' => 'TEST101',
            'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
    }

    private function registration(): array
    {
        return [
            'name' => 'Dosen Baru', 'email' => 'DOSEN.BARU@example.test',
            'institution' => 'Kampus Uji', 'nim_nip' => 'NIDN123456', 'phone' => '08123456789',
            'password' => 'password-uji-123', 'password_confirmation' => 'password-uji-123',
        ];
    }

    public function test_registration_creates_pending_lecturer_and_notifies_only_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $anotherAdmin = User::factory()->admin()->create();
        $regularLecturer = User::factory()->activeLecturer()->create();
        $this->get(route('register.lecturer'))->assertOk()->assertSee('Daftar Akun Dosen');

        $this->post(route('register.lecturer'), array_merge($this->registration(), [
            'role' => 'mahasiswa', 'is_admin' => true, 'lecturer_activated_at' => now(),
        ]))->assertRedirect(route('activation.show'));

        $lecturer = User::where('email', 'dosen.baru@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($lecturer);
        $this->assertTrue($lecturer->isDosen());
        $this->assertTrue($lecturer->needsLecturerActivation());
        $this->assertFalse($lecturer->isAdmin());
        $this->assertTrue(Hash::check('password-uji-123', $lecturer->password));
        $this->assertSame(2, Notification::where('type', 'lecturer_registration')->count());
        foreach ([$admin, $anotherAdmin] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id, 'type' => 'lecturer_registration',
                'link' => route('admin.lecturers.show', $lecturer, false),
            ]);
        }
        $this->assertDatabaseMissing('notifications', ['user_id' => $regularLecturer->id]);
    }

    public function test_registration_rejects_duplicate_email_after_normalizing_case(): void
    {
        User::factory()->create(['email' => 'dosen.baru@example.test']);
        $this->post(route('register.lecturer'), $this->registration())->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function test_pending_login_ignores_intended_feature_url(): void
    {
        $lecturer = $this->pending();
        $this->withSession(['url.intended' => route('courses.create')])
            ->post(route('login'), ['email' => $lecturer->email, 'password' => 'password'])
            ->assertRedirect(route('activation.show'));
        $this->get(route('activation.show'))->assertOk()->assertSee('Aktifkan akun dosen')
            ->assertDontSee('href="'.route('courses.create').'"', false);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pending_account_is_blocked_on_reads_writes_and_json(): void
    {
        $lecturer = $this->pending();
        $course = $this->course($lecturer);
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Uji', 'date' => today()]);
        $material = $meeting->materials()->create(['title' => 'Materi', 'type' => 'text', 'content' => 'Isi']);
        $this->actingAs($lecturer);
        foreach ([route('dashboard'), route('courses.show', $course), route('courses.create'),
            route('materials.preview', $material), route('materials.download', $material), route('profile.edit'),
            route('notifications.index'), route('admin.lecturers.index')] as $url) {
            $this->get($url)->assertRedirect(route('activation.show'));
        }
        $this->post(route('courses.store'), [])->assertRedirect(route('activation.show'));
        $this->put(route('courses.update', $course), ['name' => 'Tidak boleh'])->assertRedirect(route('activation.show'));
        $this->getJson(route('analytics.data', $course))->assertForbidden()->assertJson(['activation_required' => true]);
        $this->assertSame('Kelas Uji', $course->fresh()->name);
    }

    public function test_every_authenticated_route_has_activation_middleware(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (in_array('auth', $route->gatherMiddleware(), true)) {
                $this->assertContains('lecturer.active', $route->gatherMiddleware(), $route->uri());
            }
        }
    }

    public function test_admin_routes_and_code_generation_are_forbidden_to_other_roles(): void
    {
        $pending = $this->pending();
        foreach ([User::factory()->activeLecturer()->create(), User::factory()->create(['role' => User::ROLE_MAHASISWA])] as $user) {
            $this->actingAs($user);
            foreach ([route('admin.lecturers.index'), route('admin.lecturers.show', $pending),
                route('admin.settings.edit'), route('admin.students.index'), route('admin.backups.index'),
                route('admin.activity.index'), route('admin.semesters.index'), route('admin.ai.edit')] as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post(route('admin.lecturers.issueCode', $pending), ['payment_confirmed' => 1])->assertForbidden();
        }
        $this->assertDatabaseCount('lecturer_activation_codes', 0);
    }

    public function test_admin_issues_exactly_twenty_alphanumeric_characters_and_only_hash_is_persisted(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = $this->pending();
        $response = $this->actingAs($admin)->post(route('admin.lecturers.issueCode', $lecturer), ['payment_confirmed' => 1]);
        $response->assertRedirect(route('admin.lecturers.show', $lecturer))->assertSessionHas('issued_activation');
        $plain = session('issued_activation.code');
        $this->assertMatchesRegularExpression('/\A[A-Z0-9]{20}\z/', $plain);
        $this->assertMatchesRegularExpression('/[A-Z]/', $plain);
        $this->assertMatchesRegularExpression('/[0-9]/', $plain);
        $code = LecturerActivationCode::sole();
        $this->assertSame(hash('sha256', $plain), $code->code_hash);
        $this->assertSame($admin->id, $code->created_by);
        $this->assertSame($lecturer->id, $code->user_id);
        $this->assertTrue($lecturer->fresh()->needsLecturerActivation());
        $this->assertStringNotContainsString($plain, json_encode(DB::table('lecturer_activation_codes')->get()));
        $this->assertStringNotContainsString($plain, json_encode(DB::table('activity_logs')->get()));
        $view = $this->get(route('admin.lecturers.show', $lecturer))->assertOk()->assertSee($plain);
        $this->assertStringContainsString('no-store', $view->headers->get('Cache-Control'));
        $this->get(route('admin.lecturers.show', $lecturer))->assertOk()->assertDontSee($plain);
    }

    public function test_admin_must_confirm_payment_and_cannot_issue_for_student_or_active_lecturer(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.lecturers.issueCode', $this->pending()))->assertSessionHasErrors('payment_confirmed');
        $this->post(route('admin.lecturers.issueCode', User::factory()->create()), ['payment_confirmed' => 1])->assertNotFound();
        $this->post(route('admin.lecturers.issueCode', $admin), ['payment_confirmed' => 1])->assertSessionHasErrors('activation');
        $this->assertDatabaseCount('lecturer_activation_codes', 0);
    }

    public function test_valid_code_activates_permanently_and_does_not_grant_admin(): void
    {
        $lecturer = $this->pending();
        $issued = app(LecturerActivation::class)->issue($lecturer, User::factory()->admin()->create());
        $formatted = strtolower(implode('-', str_split($issued['plain'], 5)));
        $this->actingAs($lecturer)->post(route('activation.store'), ['activation_code' => ' '.$formatted.' '])
            ->assertRedirect(route('dashboard.dosen'));
        $this->assertFalse($lecturer->fresh()->needsLecturerActivation());
        $this->assertFalse($lecturer->fresh()->isAdmin());
        $this->assertNotNull($issued['code']->fresh()->used_at);
        $this->travel(10)->years();
        $this->actingAs($lecturer->fresh())->get(route('courses.create'))->assertOk();
        $this->get(route('activation.show'))->assertRedirect(route('dashboard'));
    }

    public function test_wrong_account_wrong_code_expired_and_replaced_codes_cannot_activate(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = $this->pending();
        $other = $this->pending();
        $service = app(LecturerActivation::class);
        $first = $service->issue($lecturer, $admin);
        $this->actingAs($other)->post(route('activation.store'), ['activation_code' => $first['plain']])->assertSessionHasErrors('activation_code');
        $this->assertTrue($other->fresh()->needsLecturerActivation());
        $this->actingAs($lecturer)->post(route('activation.store'), ['activation_code' => str_repeat('Z', 20)])->assertSessionHasErrors('activation_code');
        $this->assertNull(session('_old_input.activation_code'));
        $second = $service->issue($lecturer, $admin);
        $this->assertNotNull($first['code']->fresh()->revoked_at);
        $this->post(route('activation.store'), ['activation_code' => $first['plain']])->assertSessionHasErrors('activation_code');
        $second['code']->update(['expires_at' => now()->subSecond()]);
        $this->post(route('activation.store'), ['activation_code' => $second['plain']])->assertSessionHasErrors('activation_code');
        $this->assertTrue($lecturer->fresh()->needsLecturerActivation());
        $this->assertSame(0, LecturerActivationCode::whereNotNull('used_at')->count());
    }

    public function test_used_code_cannot_be_replayed(): void
    {
        $lecturer = $this->pending();
        $issued = app(LecturerActivation::class)->issue($lecturer, User::factory()->admin()->create());
        $this->actingAs($lecturer)->post(route('activation.store'), ['activation_code' => $issued['plain']])->assertRedirect(route('dashboard.dosen'));
        $activatedAt = $lecturer->fresh()->lecturer_activated_at;
        $this->actingAs($lecturer->fresh())->post(route('activation.store'), ['activation_code' => $issued['plain']])->assertSessionHasErrors('activation_code');
        $this->assertTrue($lecturer->fresh()->lecturer_activated_at->equalTo($activatedAt));
    }

    public function test_activation_attempts_are_rate_limited(): void
    {
        $this->actingAs($this->pending());
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('activation.store'), ['activation_code' => 'INVALID'])->assertSessionHasErrors('activation_code');
        }
        $this->post(route('activation.store'), ['activation_code' => 'INVALID'])->assertStatus(429);
    }

    public function test_active_lecturer_still_cannot_access_another_lecturers_class_or_global_student_password(): void
    {
        $owner = User::factory()->activeLecturer()->create();
        $other = User::factory()->activeLecturer()->create();
        $course = $this->course($owner);
        $student = User::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $this->actingAs($other)->get(route('courses.show', $course))->assertForbidden();
        $this->put(route('courses.update', $course), ['name' => 'Tidak boleh'])->assertForbidden();
        $this->actingAs($owner)->post(route('enrollments.resetPassword', [$course, $student]))->assertForbidden();
        $this->get(route('dashboard.dosen'))->assertOk()->assertDontSee('Kelola Dosen');
    }

    public function test_student_can_still_join_classes_from_multiple_lecturers_without_activation(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $this->actingAs($student);
        foreach ([User::factory()->activeLecturer()->create(), User::factory()->activeLecturer()->create()] as $lecturer) {
            $course = $this->course($lecturer);
            $this->post(route('enrollments.join'), ['join_code' => $course->join_code])->assertRedirect(route('courses.show', $course));
            $this->get(route('courses.show', $course))->assertOk();
        }
        $this->assertSame(2, $student->enrolledCourses()->count());
        $this->get(route('activation.show'))->assertForbidden();
    }

    public function test_lecturer_can_only_select_students_from_their_own_roster(): void
    {
        $owner = User::factory()->activeLecturer()->create();
        $other = User::factory()->activeLecturer()->create();
        $existing = $this->course($owner);
        $target = $this->course($owner);
        $foreign = $this->course($other);
        $known = User::factory()->create(['name' => 'Mahasiswa Dikenal']);
        $unknown = User::factory()->create(['name' => 'Mahasiswa Kampus Lain']);
        $existing->students()->attach($known->id, ['enrolled_at' => now()]);
        $foreign->students()->attach($unknown->id, ['enrolled_at' => now()]);

        $this->actingAs($owner)->get(route('courses.students', $target))->assertOk()
            ->assertSee($known->name)->assertDontSee($unknown->name);
        $this->post(route('enrollments.store', $target), ['user_ids' => [$known->id, $unknown->id]])
            ->assertRedirect();
        $this->assertTrue($target->students()->whereKey($known->id)->exists());
        $this->assertFalse($target->students()->whereKey($unknown->id)->exists());
    }

    public function test_explicit_admin_bootstrap_does_not_promote_a_student(): void
    {
        $lecturer = $this->pending();
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $this->artisan('lms:make-admin', ['email' => $student->email])->assertFailed();
        $this->assertFalse($student->fresh()->isAdmin());
        $this->artisan('lms:make-admin', ['email' => $lecturer->email])->assertSuccessful();
        $this->assertTrue($lecturer->fresh()->isAdmin());
        $this->assertFalse($lecturer->fresh()->needsLecturerActivation());
    }

    public function test_migration_preserves_existing_lecturers_without_granting_admin(): void
    {
        $migration = require database_path('migrations/2026_08_27_000000_add_lecturer_activation.php');
        $migration->down();
        $id = DB::table('users')->insertGetId([
            'name' => 'Dosen Lama', 'email' => 'lama@example.test', 'password' => Hash::make('password'), 'role' => 'dosen',
        ]);
        $migration->up();
        $old = User::findOrFail($id);
        $this->assertFalse($old->needsLecturerActivation());
        $this->assertFalse($old->isAdmin());
        $this->assertTrue($this->pending()->needsLecturerActivation());
    }

    public function test_demo_does_not_allow_paid_registration_or_activation_code_issuance(): void
    {
        config(['demo.enabled' => true]);
        $this->get(route('register.lecturer'))->assertNotFound();
        $this->post(route('register.lecturer'), $this->registration())->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.lecturers.issueCode', $this->pending()), ['payment_confirmed' => 1])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('lecturer_activation_codes', 0);
    }
}
