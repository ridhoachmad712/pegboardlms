<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentBulkTest extends TestCase
{
    use RefreshDatabase;

    private function course(User $lecturer): Course
    {
        return Course::create([
            'user_id' => $lecturer->id, 'name' => 'Kelas Bulk', 'code' => 'BLK101',
            'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
    }

    private function enroll(Course $course, User ...$students): void
    {
        foreach ($students as $s) {
            $course->students()->attach($s->id, ['enrolled_at' => now()]);
        }
    }

    public function test_lecturer_bulk_removes_selected_students_from_own_course(): void
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $course = $this->course($lecturer);
        $s1 = User::factory()->create(['role' => 'mahasiswa']);
        $s2 = User::factory()->create(['role' => 'mahasiswa']);
        $s3 = User::factory()->create(['role' => 'mahasiswa']);
        $this->enroll($course, $s1, $s2, $s3);

        $this->actingAs($lecturer)->withSession(['lecturer_session_version' => 0]);
        $this->post(route('enrollments.bulkDestroy', $course), ['ids' => [$s1->id, $s2->id]])
            ->assertRedirect();

        $this->assertEqualsCanonicalizing([$s3->id], $course->students()->pluck('users.id')->all());
    }

    public function test_bulk_unenroll_requires_ownership_and_ignores_foreign_ids(): void
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $course = $this->course($lecturer);
        $enrolled = User::factory()->create(['role' => 'mahasiswa']);
        $outsider = User::factory()->create(['role' => 'mahasiswa']);
        $this->enroll($course, $enrolled);

        // Non-owner lecturer cannot touch this course.
        $other = User::factory()->activeLecturer()->create();
        $this->actingAs($other)->withSession(['lecturer_session_version' => 0]);
        $this->post(route('enrollments.bulkDestroy', $course), ['ids' => [$enrolled->id]])->assertForbidden();
        $this->assertTrue($course->students()->whereKey($enrolled->id)->exists());

        // Owner: ids that are not enrolled are silently ignored, enrolled ones are removed.
        $this->actingAs($lecturer)->withSession(['lecturer_session_version' => 0]);
        $this->post(route('enrollments.bulkDestroy', $course), ['ids' => [$enrolled->id, $outsider->id]])->assertRedirect();
        $this->assertSame(0, $course->students()->count());
    }
}
