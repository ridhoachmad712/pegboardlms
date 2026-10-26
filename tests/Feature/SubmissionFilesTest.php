<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionFilesTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $student = User::factory()->create();
        $outsider = User::factory()->create();
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Pengolahan Data',
            'code' => 'DAT101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $assignment = $course->assignments()->create([
            'title' => 'Analisis Dataset',
            'type' => Assignment::TYPE_TUGAS,
            'submission_mode' => Assignment::SUBMISSION_FILE,
            'mode' => Assignment::MODE_INDIVIDU,
            'max_score' => 100,
            'published' => true,
        ]);

        return compact('lecturer', 'student', 'outsider', 'course', 'assignment');
    }

    public function test_student_can_submit_two_files_including_excel_and_csv(): void
    {
        Storage::fake('local');
        $context = $this->context();

        $this->actingAs($context['student'])
            ->post(route('submissions.store', $context['assignment']), [
                'files' => [
                    UploadedFile::fake()->create('perhitungan.xls', 12, 'application/vnd.ms-excel'),
                    UploadedFile::fake()->create('dataset.csv', 8, 'text/csv'),
                ],
            ])->assertRedirect();

        $submission = Submission::firstOrFail();
        $this->assertCount(2, $submission->filePaths());
        $this->assertStringEndsWith('.xls', $submission->file_path);
        $this->assertStringEndsWith('.csv', $submission->file_path_2);
        Storage::disk('local')->assertExists($submission->file_path);
        Storage::disk('local')->assertExists($submission->file_path_2);
    }

    public function test_submission_rejects_more_than_two_files(): void
    {
        Storage::fake('local');
        $context = $this->context();

        $this->actingAs($context['student'])
            ->post(route('submissions.store', $context['assignment']), [
                'files' => [
                    UploadedFile::fake()->create('satu.pdf', 5, 'application/pdf'),
                    UploadedFile::fake()->create('dua.csv', 5, 'text/csv'),
                    UploadedFile::fake()->create('tiga.txt', 5, 'text/plain'),
                ],
            ])->assertSessionHasErrors('files');

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_new_upload_replaces_both_old_files_and_empty_upload_keeps_them(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('submissions/old-one.pdf', 'old');
        Storage::disk('local')->put('submissions/old-two.csv', 'old');
        $submission = $context['assignment']->submissions()->create([
            'user_id' => $context['student']->id,
            'file_path' => 'submissions/old-one.pdf',
            'file_path_2' => 'submissions/old-two.csv',
            'status' => 'ontime',
            'submitted_at' => now(),
        ]);

        $this->actingAs($context['student'])
            ->post(route('submissions.store', $context['assignment']), [])
            ->assertRedirect();
        $this->assertSame('submissions/old-one.pdf', $submission->fresh()->file_path);
        $this->assertSame('submissions/old-two.csv', $submission->fresh()->file_path_2);

        $this->actingAs($context['student'])
            ->post(route('submissions.store', $context['assignment']), [
                'files' => [UploadedFile::fake()->create('pengganti.pdf', 5, 'application/pdf')],
            ])->assertRedirect();

        $submission->refresh();
        $this->assertNotNull($submission->file_path);
        $this->assertNull($submission->file_path_2);
        Storage::disk('local')->assertMissing('submissions/old-one.pdf');
        Storage::disk('local')->assertMissing('submissions/old-two.csv');
        Storage::disk('local')->assertExists($submission->file_path);
    }

    public function test_each_file_can_be_downloaded_only_by_student_or_course_lecturer(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('submissions/one.pdf', 'first');
        Storage::disk('local')->put('submissions/two.csv', 'second');
        $submission = $context['assignment']->submissions()->create([
            'user_id' => $context['student']->id,
            'file_path' => 'submissions/one.pdf',
            'file_path_2' => 'submissions/two.csv',
            'status' => 'ontime',
            'submitted_at' => now(),
        ]);

        $this->actingAs($context['student'])
            ->get(route('submissions.download', [$submission, 'slot' => 2]))
            ->assertOk();
        $this->actingAs($context['lecturer'])
            ->get(route('submissions.download', [$submission, 'slot' => 1]))
            ->assertOk();
        $this->actingAs($context['outsider'])
            ->get(route('submissions.download', [$submission, 'slot' => 2]))
            ->assertForbidden();
    }

    public function test_deleting_submission_removes_both_files(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('submissions/one.pdf', 'first');
        Storage::disk('local')->put('submissions/two.csv', 'second');
        $submission = $context['assignment']->submissions()->create([
            'user_id' => $context['student']->id,
            'file_path' => 'submissions/one.pdf',
            'file_path_2' => 'submissions/two.csv',
            'status' => 'ontime',
            'submitted_at' => now(),
        ]);

        $this->actingAs($context['student'])
            ->delete(route('submissions.destroy', $submission))
            ->assertRedirect();

        Storage::disk('local')->assertMissing('submissions/one.pdf');
        Storage::disk('local')->assertMissing('submissions/two.csv');
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    }
}
