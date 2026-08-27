<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Notification;
use App\Models\Submission;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function courseWithStudent(): array
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $student = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
        $course = Course::create([
            'user_id' => $lecturer->id, 'name' => 'Ekonometrika', 'code' => 'EKO101',
            'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        return [$lecturer, $student, $course];
    }

    public function test_pusat_aktivitas_hanya_untuk_mahasiswa_dan_memuat_tugas(): void
    {
        [$lecturer, $student, $course] = $this->courseWithStudent();
        $assignment = $course->assignments()->create([
            'title' => 'Laporan Regresi', 'type' => Assignment::TYPE_TUGAS,
            'deadline' => now()->addDay(), 'published' => true,
        ]);

        $this->actingAs($student)->get(route('activities.index'))
            ->assertOk()->assertSee('Laporan Regresi')->assertSee(route('assignments.show', $assignment));
        $this->actingAs($lecturer)->get(route('activities.index'))->assertForbidden();
    }

    public function test_preferensi_notifikasi_diterapkan_oleh_notifier(): void
    {
        [, $student] = $this->courseWithStudent();
        $this->actingAs($student)->put(route('notifications.preferences'), [
            'announcement' => '1', 'grade' => '0',
        ])->assertRedirect();

        Notifier::toUser($student->fresh(), 'grade', 'Nilai tersedia');
        Notifier::toUser($student->fresh(), 'announcement', 'Pengumuman baru');

        $this->assertDatabaseMissing('notifications', ['user_id' => $student->id, 'type' => 'grade']);
        $this->assertDatabaseHas('notifications', ['user_id' => $student->id, 'type' => 'announcement']);
    }

    public function test_pencarian_materi_hanya_dari_kelas_yang_diikuti(): void
    {
        [$lecturer, $student, $course] = $this->courseWithStudent();
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Regresi Linear', 'date' => today()]);
        $meeting->materials()->create(['title' => 'Modul Regresi', 'type' => 'text', 'content' => 'Pembahasan model regresi']);

        $other = Course::create([
            'user_id' => $lecturer->id, 'name' => 'Kelas Lain', 'code' => 'LAIN',
            'semester' => 'Ganjil', 'year' => 2026, 'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $otherMeeting = $other->meetings()->create(['number' => 1, 'topic' => 'Rahasia Regresi', 'date' => today()]);
        $otherMeeting->materials()->create(['title' => 'Modul Rahasia', 'type' => 'text', 'content' => 'regresi']);

        $this->actingAs($student)->get(route('search', ['q' => 'regresi']))
            ->assertOk()->assertSee('Modul Regresi')->assertDontSee('Modul Rahasia');
    }

    public function test_membuka_kembali_tugas_mengirim_notifikasi_revisi(): void
    {
        [$lecturer, $student, $course] = $this->courseWithStudent();
        $assignment = $course->assignments()->create(['title' => 'Makalah', 'type' => 'tugas', 'published' => true]);
        $submission = Submission::create([
            'assignment_id' => $assignment->id, 'user_id' => $student->id,
            'status' => 'ontime', 'submitted_at' => now(), 'score' => 80,
        ]);

        $this->actingAs($lecturer)->post(route('submissions.reopen', $submission))->assertRedirect();
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id, 'type' => 'revision', 'title' => 'Tugas perlu dikumpulkan ulang',
        ]);
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    }
}
