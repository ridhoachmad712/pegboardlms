<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GradingWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        Storage::fake('local');
        $lecturer = User::factory()->activeLecturer()->create();
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Manajemen Pemasaran',
            'code' => 'MPM101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $assignment = $course->assignments()->create([
            'title' => 'Analisis Segmentasi',
            'type' => Assignment::TYPE_TUGAS,
            'submission_mode' => Assignment::SUBMISSION_BOTH,
            'mode' => Assignment::MODE_INDIVIDU,
            'max_score' => 100,
            'published' => true,
        ]);

        // Sengaja dibuat tidak urut abjad: antrean harus urut nama.
        $subs = [];
        foreach (['Citra', 'Andi', 'Budi'] as $name) {
            $student = User::factory()->create(['name' => $name]);
            $course->students()->attach($student->id, ['enrolled_at' => now()]);
            Storage::disk('local')->put("submissions/{$assignment->id}/{$name}.pdf", 'pdf');
            $subs[$name] = Submission::create([
                'assignment_id' => $assignment->id,
                'user_id' => $student->id,
                'file_path' => "submissions/{$assignment->id}/{$name}.pdf",
                'answer_text' => "Jawaban {$name}",
                'submitted_at' => now(),
            ]);
        }

        return compact('lecturer', 'course', 'assignment', 'subs');
    }

    public function test_workspace_shows_preview_and_grade_form_starting_at_first_ungraded(): void
    {
        $c = $this->context();
        $c['subs']['Andi']->update(['score' => 80]);

        $this->actingAs($c['lecturer'])
            ->get(route('grading.show', $c['assignment']))
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('2 dari 3')
            ->assertSee(route('submissions.preview', [$c['subs']['Budi'], 'slot' => 1]), false)
            ->assertSee('Jawaban Budi')
            ->assertSee(route('submissions.grade', $c['subs']['Budi']), false)
            ->assertSee('Simpan &amp; lanjut', false)
            ->assertSee('1</span><span class="text-secondary">/3 dinilai', false);
    }

    public function test_save_and_next_moves_to_next_student_in_name_order(): void
    {
        $c = $this->context();

        $this->actingAs($c['lecturer'])
            ->post(route('submissions.grade', $c['subs']['Andi']), ['score' => 85, 'feedback' => 'Bagus', 'grading' => 'next'])
            ->assertRedirect(route('grading.show', [$c['assignment'], $c['subs']['Budi']]))
            ->assertSessionHas('status');

        $this->assertEquals(85, $c['subs']['Andi']->fresh()->score);
        $this->assertSame('Bagus', $c['subs']['Andi']->fresh()->feedback);
    }

    public function test_save_and_next_can_skip_graded_and_wraps_around(): void
    {
        $c = $this->context();
        $c['subs']['Citra']->update(['score' => 70]);

        // Budi → (Citra sudah dinilai, dilewati) → kembali ke Andi.
        $this->actingAs($c['lecturer'])
            ->post(route('submissions.grade', $c['subs']['Budi']), ['score' => 90, 'grading' => 'next', 'ungraded' => 1])
            ->assertRedirect(route('grading.show', [$c['assignment'], $c['subs']['Andi'], 'ungraded' => 1]));

        // Andi terakhir yang belum dinilai → tetap di halaman Andi dengan pesan selesai.
        $this->post(route('submissions.grade', $c['subs']['Andi']), ['score' => 75, 'grading' => 'next', 'ungraded' => 1])
            ->assertRedirect(route('grading.show', [$c['assignment'], $c['subs']['Andi'], 'ungraded' => 1]))
            ->assertSessionHas('status', fn ($msg) => str_contains($msg, 'Tidak ada lagi pengumpulan yang belum dinilai'));
    }

    public function test_save_stays_on_same_submission_and_plain_grade_still_redirects_back(): void
    {
        $c = $this->context();

        $this->actingAs($c['lecturer'])
            ->post(route('submissions.grade', $c['subs']['Citra']), ['score' => 60, 'grading' => 'stay'])
            ->assertRedirect(route('grading.show', [$c['assignment'], $c['subs']['Citra']]));

        $this->from(route('assignments.show', $c['assignment']))
            ->post(route('submissions.grade', $c['subs']['Citra']), ['score' => 65])
            ->assertRedirect(route('assignments.show', $c['assignment']));
    }

    public function test_prev_next_links_follow_queue_order(): void
    {
        $c = $this->context();

        $this->actingAs($c['lecturer'])
            ->get(route('grading.show', [$c['assignment'], $c['subs']['Budi']]))
            ->assertOk()
            ->assertSee('id="nav-prev"', false)
            ->assertSee('href="'.route('grading.show', [$c['assignment'], $c['subs']['Andi']]).'" class="btn btn-icon "', false)
            ->assertSee('href="'.route('grading.show', [$c['assignment'], $c['subs']['Citra']]).'" class="btn btn-icon "', false);
    }

    public function test_only_course_owner_can_open_workspace_and_submission_must_match_assignment(): void
    {
        $c = $this->context();
        $other = $this->context();

        $this->actingAs($c['lecturer'])
            ->get(route('grading.show', [$c['assignment'], $other['subs']['Andi']]))
            ->assertNotFound();

        $this->actingAs($other['lecturer'])
            ->get(route('grading.show', $c['assignment']))
            ->assertForbidden();

        $student = $c['subs']['Andi']->student;
        $this->actingAs($student)->get(route('grading.show', $c['assignment']))->assertForbidden();
    }

    public function test_rubric_grading_from_workspace_sums_points_and_moves_on(): void
    {
        $c = $this->context();
        $isi = $c['assignment']->rubricCriteria()->create(['name' => 'Isi', 'max_points' => 60]);
        $bahasa = $c['assignment']->rubricCriteria()->create(['name' => 'Bahasa', 'max_points' => 40]);

        $this->actingAs($c['lecturer'])
            ->get(route('grading.show', [$c['assignment'], $c['subs']['Andi']]))
            ->assertOk()
            ->assertSee('name="rubric['.$isi->id.']"', false)
            ->assertDontSee('name="score"', false);

        $this->post(route('submissions.grade', $c['subs']['Andi']), [
            'rubric' => [$isi->id => 50, $bahasa->id => 99],
            'grading' => 'next',
        ])->assertRedirect(route('grading.show', [$c['assignment'], $c['subs']['Budi']]));

        $this->assertEquals(90, $c['subs']['Andi']->fresh()->score);
    }

    public function test_assignment_page_links_to_workspace(): void
    {
        $c = $this->context();

        $this->actingAs($c['lecturer'])
            ->get(route('assignments.show', $c['assignment']))
            ->assertOk()
            ->assertSee(route('grading.show', $c['assignment']), false)
            ->assertSee(route('grading.show', [$c['assignment'], $c['subs']['Andi']]), false);
    }
}
