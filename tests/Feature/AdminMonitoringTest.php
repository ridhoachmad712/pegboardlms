<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Meeting;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        Storage::fake('local');
        config(['demo.enabled' => false]);
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->activeLecturer()->create(['name' => 'Dosen Pengampu']);
        $student = User::factory()->create(['name' => 'Mahasiswa Satu', 'nim_nip' => '2109001']);
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Ekonomi Mikro',
            'code' => 'EKM101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Permintaan & Penawaran', 'date' => today()]);
        Storage::disk('local')->put('materials/1/modul.pdf', 'pdf');
        $material = $meeting->materials()->create(['title' => 'Modul 1', 'type' => Material::TYPE_FILE, 'path' => 'materials/1/modul.pdf', 'mime' => 'application/pdf']);
        $assignment = $course->assignments()->create([
            'title' => 'Analisis Elastisitas',
            'type' => Assignment::TYPE_TUGAS,
            'submission_mode' => Assignment::SUBMISSION_FILE,
            'mode' => Assignment::MODE_INDIVIDU,
            'max_score' => 100,
            'published' => true,
        ]);
        Storage::disk('local')->put('submissions/1/jawaban.pdf', 'pdf');
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/1/jawaban.pdf',
            'submitted_at' => now(),
        ]);

        return compact('admin', 'lecturer', 'student', 'course', 'meeting', 'material', 'assignment', 'submission');
    }

    public function test_admin_lists_all_courses_with_filters(): void
    {
        $c = $this->context();
        $other = User::factory()->activeLecturer()->create(['name' => 'Dosen Lain']);
        Course::create([
            'user_id' => $other->id, 'name' => 'Statistika', 'code' => 'STA101', 'semester' => 'Genap',
            'year' => 2025, 'status' => Course::STATUS_COMPLETED, 'join_code' => Course::generateJoinCode(),
        ]);

        $this->actingAs($c['admin'])->get(route('admin.courses.index'))
            ->assertOk()->assertSee('Ekonomi Mikro')->assertSee('Statistika')->assertSee('Dosen Pengampu')
            ->assertSee(route('courses.show', $c['course']), false);

        $this->get(route('admin.courses.index', ['q' => 'Dosen Lain']))->assertOk()->assertSee('Statistika')->assertDontSee('Ekonomi Mikro');
        $this->get(route('admin.courses.index', ['status' => Course::STATUS_ACTIVE]))->assertOk()->assertSee('Ekonomi Mikro')->assertDontSee('Statistika');
        $this->get(route('admin.courses.index', ['periode' => '2025-Genap']))->assertOk()->assertSee('Statistika')->assertDontSee('Ekonomi Mikro');

        $this->actingAs($c['lecturer'])->get(route('admin.courses.index'))->assertForbidden();
    }

    public function test_admin_can_view_every_page_of_another_lecturers_course_in_monitor_mode(): void
    {
        $c = $this->context();
        $this->actingAs($c['admin']);

        $pages = [
            route('courses.show', $c['course']),
            route('courses.materials', $c['course']),
            route('courses.students', $c['course']),
            route('assignments.index', $c['course']),
            route('assignments.show', $c['assignment']),
            route('grading.show', $c['assignment']),
            route('grades.index', $c['course']),
            route('attendance.index', $c['course']),
            route('attendance.session', $c['meeting']),
            route('announcements.index', $c['course']),
            route('forum.index', $c['course']),
            route('syllabus.show', $c['course']),
            route('reports.index', $c['course']),
            route('analytics.index', $c['course']),
        ];
        foreach ($pages as $url) {
            $this->get($url)->assertOk()->assertSee('Mode pantau admin');
        }

        $this->get(route('materials.download', $c['material']))->assertOk();
        $this->get(route('submissions.download', $c['submission']))->assertOk();
        $this->get(route('export.nilai.excel', $c['course']))->assertOk();

        // Melihat halaman sesi absensi tidak boleh membuka/membuat token absensi.
        $this->assertSame(0, $c['meeting']->tokens()->count());
    }

    public function test_admin_cannot_change_anything_in_monitored_course(): void
    {
        $c = $this->context();
        $this->actingAs($c['admin']);

        $this->put(route('courses.update', $c['course']), ['name' => 'Diubah'])->assertForbidden();
        $this->delete(route('courses.destroy', $c['course']))->assertForbidden();
        $this->post(route('meetings.store', $c['course']), ['number' => 2, 'topic' => 'X', 'type' => Meeting::TYPE_TATAP_MUKA])->assertForbidden();
        $this->post(route('meetings.generate', $c['course']), ['count' => 16, 'first_date' => '2026-09-01', 'interval_days' => 7, 'type' => Meeting::TYPE_TATAP_MUKA])->assertForbidden();
        $this->delete(route('materials.destroy', $c['material']))->assertForbidden();
        $this->post(route('submissions.grade', $c['submission']), ['score' => 90])->assertForbidden();
        $this->post(route('attendance.update', $c['meeting']), ['statuses' => [$c['student']->id => 'hadir']])->assertForbidden();
        $this->post(route('announcements.store', $c['course']), ['title' => 'X', 'content' => 'Y'])->assertForbidden();
        $this->post(route('forum.threads.store', $c['course']), ['title' => 'X', 'body' => 'Y'])->assertForbidden();
        $this->post(route('grades.saveManual', $c['course']), [])->assertForbidden();
        $this->post(route('enrollments.store', $c['course']), ['nim' => 'x'])->assertForbidden();
        $this->delete(route('enrollments.destroy', [$c['course'], $c['student']]))->assertForbidden();

        $this->assertSame('Ekonomi Mikro', $c['course']->fresh()->name);
        $this->assertSame(1, $c['course']->meetings()->count());
        $this->assertNull($c['submission']->fresh()->score);
        $this->assertSame(0, $c['meeting']->attendances()->count());
        $this->assertTrue(Material::whereKey($c['material']->id)->exists());
    }

    public function test_non_admin_lecturer_still_cannot_view_other_courses_and_owner_sees_no_banner(): void
    {
        $c = $this->context();

        $this->actingAs(User::factory()->activeLecturer()->create())
            ->get(route('courses.show', $c['course']))->assertForbidden();
        $this->get(route('grades.index', $c['course']))->assertForbidden();

        $this->actingAs($c['lecturer'])->get(route('courses.show', $c['course']))
            ->assertOk()->assertDontSee('Mode pantau admin');

        $ownCourse = Course::create([
            'user_id' => $c['admin']->id, 'name' => 'Kelas Admin', 'code' => 'ADM101', 'semester' => 'Ganjil',
            'year' => 2026, 'status' => Course::STATUS_ACTIVE, 'join_code' => Course::generateJoinCode(),
        ]);
        $this->actingAs($c['admin'])->get(route('courses.show', $ownCourse))->assertOk()->assertDontSee('Mode pantau admin');
        $this->put(route('courses.update', $ownCourse), [])->assertSessionHasErrors();
    }
}
