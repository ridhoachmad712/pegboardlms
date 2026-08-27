<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MakeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_student_promotion_preserves_identity_password_and_academic_history(): void
    {
        config(['demo.enabled' => false]);
        $student = User::factory()->create(['email' => 'OWNER@EXAMPLE.TEST']);
        $lecturer = User::factory()->activeLecturer()->create();
        $otherStudent = User::factory()->create();
        $course = Course::create([
            'user_id' => $lecturer->id, 'name' => 'Kelas Uji', 'code' => 'TEST101',
            'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $enrollment = Enrollment::create([
            'user_id' => $student->id, 'course_id' => $course->id, 'enrolled_at' => now(),
        ]);
        $assignment = Assignment::create(['course_id' => $course->id, 'title' => 'Tugas Uji', 'type' => 'tugas']);
        $submission = Submission::create([
            'assignment_id' => $assignment->id, 'user_id' => $student->id,
            'answer_text' => 'Jawaban lama', 'score' => 85, 'feedback' => 'Nilai lama', 'status' => 'ontime',
        ]);
        $before = $student->fresh()->getRawOriginal();
        $others = DB::table('users')->where('id', '!=', $student->id)->orderBy('id')->get();
        $enrollmentBefore = $enrollment->fresh()->getRawOriginal();
        $courseBefore = $course->fresh()->getRawOriginal();
        $submissionBefore = $submission->fresh()->getRawOriginal();

        $this->artisan('lms:make-admin', [
            'email' => ' owner@example.test ', '--promote-student' => true, '--no-interaction' => true,
        ])->assertSuccessful();

        $admin = $student->fresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->needsLecturerActivation());
        $changed = ['role', 'is_admin', 'lecturer_activated_at', 'updated_at'];
        $this->assertSame(Arr::except($before, $changed), Arr::except($admin->getRawOriginal(), $changed));
        $this->assertSame($enrollmentBefore, $enrollment->fresh()->getRawOriginal());
        $this->assertSame($courseBefore, $course->fresh()->getRawOriginal());
        $this->assertSame($submissionBefore, $submission->fresh()->getRawOriginal());
        $this->assertEquals($others, DB::table('users')->where('id', '!=', $student->id)->orderBy('id')->get());
        $this->assertDatabaseCount('users', 3);
        $this->assertSame(1, User::where('is_admin', true)->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin_setup']);
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('admin.lecturers.index'))->assertOk();
        $this->assertFalse($otherStudent->fresh()->isAdmin());
    }

    public function test_student_is_not_promoted_without_explicit_option(): void
    {
        $student = User::factory()->create();
        $before = $student->fresh()->getRawOriginal();
        $this->artisan('lms:make-admin', ['email' => $student->email])->assertFailed();
        $this->assertSame($before, $student->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_missing_email_does_not_create_or_change_accounts(): void
    {
        User::factory()->create();
        $before = DB::table('users')->get();
        $this->artisan('lms:make-admin', ['email' => 'missing@example.test', '--promote-student' => true])
            ->assertFailed();
        $this->assertEquals($before, DB::table('users')->get());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_lecturer_promotion_and_repeat_keep_activation_date_and_password(): void
    {
        $lecturer = User::factory()->activeLecturer()->create(['lecturer_activated_at' => '2026-01-01 00:00:00']);
        $before = $lecturer->fresh()->getRawOriginal();
        $this->artisan('lms:make-admin', ['email' => $lecturer->email])->assertSuccessful();
        $admin = $lecturer->fresh();
        $this->assertTrue($admin->isAdmin());
        $this->assertSame($before['lecturer_activated_at'], $admin->getRawOriginal('lecturer_activated_at'));
        $this->assertSame($before['password'], $admin->getRawOriginal('password'));
        $after = $admin->getRawOriginal();
        $this->travel(1)->hours();
        $this->artisan('lms:make-admin', ['email' => $admin->email, '--promote-student' => true])->assertSuccessful();
        $this->assertSame($after, $admin->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_audit_failure_rolls_back_the_role_change(): void
    {
        $student = User::factory()->create();
        $before = $student->fresh()->getRawOriginal();
        ActivityLog::creating(function () {
            throw new RuntimeException('Synthetic audit failure');
        });
        try {
            $this->artisan('lms:make-admin', ['email' => $student->email, '--promote-student' => true])->run();
            $this->fail('Expected audit failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic audit failure', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }
        $this->assertSame($before, $student->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_missing_migration_fails_without_changing_user(): void
    {
        $migration = require database_path('migrations/2026_08_27_000000_add_lecturer_activation.php');
        $migration->down();
        try {
            $student = User::factory()->create();
            $before = $student->fresh()->getRawOriginal();
            $this->artisan('lms:make-admin', ['email' => $student->email, '--promote-student' => true])
                ->expectsOutputToContain('php artisan migrate --force')->assertFailed();
            $this->assertSame($before, $student->fresh()->getRawOriginal());
            $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
        } finally {
            $migration->up();
        }
    }
}
