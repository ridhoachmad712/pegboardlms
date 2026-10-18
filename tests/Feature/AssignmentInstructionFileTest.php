<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentInstructionFileTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $student = User::factory()->create();
        $outsider = User::factory()->create();
        $otherLecturer = User::factory()->activeLecturer()->create();
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Metode Penelitian',
            'code' => 'MET101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student, ['enrolled_at' => now()]);
        $meeting = $course->meetings()->create([
            'number' => 1,
            'topic' => 'Proposal penelitian',
            'date' => today(),
        ]);

        return compact('lecturer', 'student', 'outsider', 'otherLecturer', 'course', 'meeting');
    }

    private function payload(Meeting $meeting, array $overrides = []): array
    {
        return array_merge([
            'meeting_id' => $meeting->id,
            'title' => 'Susun Proposal',
            'description' => 'Ikuti format pada lampiran.',
            'type' => Assignment::TYPE_TUGAS,
            'submission_mode' => Assignment::SUBMISSION_FILE,
            'mode' => Assignment::MODE_INDIVIDU,
            'max_score' => 100,
        ], $overrides);
    }

    private function assignment(array $context, array $attributes = []): Assignment
    {
        return $context['course']->assignments()->create(array_merge([
            'meeting_id' => $context['meeting']->id,
            'title' => 'Susun Proposal',
            'description' => 'Ikuti format pada lampiran.',
            'type' => Assignment::TYPE_TUGAS,
            'submission_mode' => Assignment::SUBMISSION_FILE,
            'mode' => Assignment::MODE_INDIVIDU,
            'max_score' => 100,
            'published' => true,
        ], $attributes));
    }

    public function test_lecturer_can_create_assignment_with_private_instruction_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $context = $this->context();

        $this->actingAs($context['lecturer'])
            ->post(route('assignments.store', $context['course']), $this->payload($context['meeting'], [
                'instruction_file' => UploadedFile::fake()->create('Panduan Proposal.pdf', 512, 'application/pdf'),
            ]))->assertRedirect();

        $assignment = Assignment::sole();
        $this->assertTrue($assignment->hasInstructionFile());
        $this->assertSame('Panduan Proposal.pdf', $assignment->instruction_file_name);
        $this->assertSame('application/pdf', $assignment->instruction_file_mime);
        $this->assertSame(512 * 1024, $assignment->instruction_file_size);
        $this->assertStringStartsWith('assignment-instructions/'.$context['course']->id.'/', $assignment->instruction_file_path);
        Storage::disk('local')->assertExists($assignment->instruction_file_path);
        Storage::disk('public')->assertMissing($assignment->instruction_file_path);
    }

    public function test_create_and_edit_forms_offer_attachment_only_for_assignments(): void
    {
        $context = $this->context();
        $assignment = $this->assignment($context);

        $this->actingAs($context['lecturer'])
            ->get(route('assignments.create', [$context['course'], 'type' => 'tugas']))
            ->assertOk()->assertSee('name="instruction_file"', false)
            ->assertSee('enctype="multipart/form-data"', false)->assertSee('Lampiran Instruksi');
        $this->get(route('assignments.edit', $assignment))
            ->assertOk()->assertSee('name="instruction_file"', false)->assertSee('Lampiran Instruksi');
        $this->get(route('assignments.create', [$context['course'], 'type' => 'kuis']))
            ->assertOk()->assertDontSee('name="instruction_file"', false);
    }

    public function test_enrolled_student_and_course_lecturer_can_download_but_outsiders_cannot(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('assignment-instructions/panduan.pdf', 'isi panduan');
        $assignment = $this->assignment($context, [
            'instruction_file_path' => 'assignment-instructions/panduan.pdf',
            'instruction_file_name' => 'Panduan Proposal.pdf',
            'instruction_file_mime' => 'application/pdf',
            'instruction_file_size' => 11,
        ]);

        $this->actingAs($context['student'])->get(route('assignments.show', $assignment))
            ->assertOk()->assertSee('Panduan Proposal.pdf')->assertSee(route('assignments.instruction.download', $assignment));
        $this->get(route('assignments.instruction.download', $assignment))
            ->assertOk()->assertDownload('Panduan Proposal.pdf')->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($context['lecturer'])->get(route('assignments.instruction.download', $assignment))
            ->assertOk()->assertDownload('Panduan Proposal.pdf');
        $this->actingAs($context['outsider'])->get(route('assignments.instruction.download', $assignment))->assertForbidden();
        $this->actingAs($context['otherLecturer'])->get(route('assignments.instruction.download', $assignment))->assertForbidden();
    }

    public function test_missing_attachment_or_missing_stored_file_returns_not_found(): void
    {
        Storage::fake('local');
        $context = $this->context();
        $withoutFile = $this->assignment($context);
        $this->actingAs($context['student'])
            ->get(route('assignments.instruction.download', $withoutFile))->assertNotFound();

        $withoutFile->forceFill([
            'instruction_file_path' => 'assignment-instructions/hilang.pdf',
            'instruction_file_name' => 'Hilang.pdf',
        ])->save();
        $this->get(route('assignments.instruction.download', $withoutFile))->assertNotFound();
    }

    public function test_upload_rejects_unsupported_or_oversized_files_without_creating_assignment(): void
    {
        Storage::fake('local');
        $context = $this->context();

        $this->actingAs($context['lecturer'])
            ->post(route('assignments.store', $context['course']), $this->payload($context['meeting'], [
                'instruction_file' => UploadedFile::fake()->create('program.exe', 10, 'application/octet-stream'),
            ]))->assertSessionHasErrors('instruction_file');
        $this->post(route('assignments.store', $context['course']), $this->payload($context['meeting'], [
            'instruction_file' => UploadedFile::fake()->create('terlalu-besar.pdf', 20481, 'application/pdf'),
        ]))->assertSessionHasErrors('instruction_file');

        $this->assertDatabaseCount('assignments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_quiz_cannot_receive_an_unvalidated_instruction_attachment(): void
    {
        Storage::fake('local');
        $context = $this->context();

        $this->actingAs($context['lecturer'])
            ->post(route('assignments.store', $context['course']), [
                'meeting_id' => $context['meeting']->id,
                'title' => 'Kuis Internal',
                'type' => Assignment::TYPE_KUIS,
                'delivery_mode' => Assignment::DELIVERY_INTERNAL,
                'max_score' => 100,
                'instruction_file' => UploadedFile::fake()->create('program.exe', 10, 'application/octet-stream'),
            ])->assertSessionHasErrors('instruction_file');

        $this->assertDatabaseCount('assignments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_update_without_upload_keeps_file_and_new_upload_replaces_it(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('assignment-instructions/lama.pdf', 'lama');
        $assignment = $this->assignment($context, [
            'instruction_file_path' => 'assignment-instructions/lama.pdf',
            'instruction_file_name' => 'Lama.pdf',
            'instruction_file_mime' => 'application/pdf',
            'instruction_file_size' => 4,
        ]);

        $this->actingAs($context['lecturer'])
            ->put(route('assignments.update', $assignment), $this->payload($context['meeting'], ['title' => 'Judul Baru']))
            ->assertRedirect(route('assignments.show', $assignment));
        $this->assertSame('assignment-instructions/lama.pdf', $assignment->fresh()->instruction_file_path);
        Storage::disk('local')->assertExists('assignment-instructions/lama.pdf');

        $this->put(route('assignments.update', $assignment), $this->payload($context['meeting'], [
            'instruction_file' => UploadedFile::fake()->create('Format Baru.docx', 25, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]))->assertRedirect(route('assignments.show', $assignment));

        $updated = $assignment->fresh();
        $this->assertSame('Format Baru.docx', $updated->instruction_file_name);
        $this->assertNotSame('assignment-instructions/lama.pdf', $updated->instruction_file_path);
        Storage::disk('local')->assertMissing('assignment-instructions/lama.pdf');
        Storage::disk('local')->assertExists($updated->instruction_file_path);
    }

    public function test_lecturer_can_remove_attachment_without_deleting_assignment(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('assignment-instructions/hapus.pdf', 'hapus');
        $assignment = $this->assignment($context, [
            'instruction_file_path' => 'assignment-instructions/hapus.pdf',
            'instruction_file_name' => 'Hapus.pdf',
            'instruction_file_mime' => 'application/pdf',
            'instruction_file_size' => 5,
        ]);

        $this->actingAs($context['lecturer'])
            ->put(route('assignments.update', $assignment), $this->payload($context['meeting'], ['remove_instruction_file' => 1]))
            ->assertRedirect(route('assignments.show', $assignment));

        $updated = $assignment->fresh();
        $this->assertNull($updated->instruction_file_path);
        $this->assertNull($updated->instruction_file_name);
        $this->assertNull($updated->instruction_file_mime);
        $this->assertNull($updated->instruction_file_size);
        Storage::disk('local')->assertMissing('assignment-instructions/hapus.pdf');
    }

    public function test_deleting_assignment_removes_its_instruction_file(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('assignment-instructions/hapus-bersama.pdf', 'hapus');
        $assignment = $this->assignment($context, [
            'instruction_file_path' => 'assignment-instructions/hapus-bersama.pdf',
            'instruction_file_name' => 'Hapus Bersama.pdf',
        ]);

        $this->actingAs($context['lecturer'])->delete(route('assignments.destroy', $assignment))->assertRedirect();

        Storage::disk('local')->assertMissing('assignment-instructions/hapus-bersama.pdf');
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_another_lecturer_cannot_replace_or_remove_attachment(): void
    {
        Storage::fake('local');
        $context = $this->context();
        Storage::disk('local')->put('assignment-instructions/aman.pdf', 'aman');
        $assignment = $this->assignment($context, [
            'instruction_file_path' => 'assignment-instructions/aman.pdf',
            'instruction_file_name' => 'Aman.pdf',
        ]);

        $this->actingAs($context['otherLecturer'])
            ->put(route('assignments.update', $assignment), $this->payload($context['meeting'], [
                'remove_instruction_file' => 1,
                'instruction_file' => UploadedFile::fake()->create('jahat.pdf', 2, 'application/pdf'),
            ]))->assertForbidden();

        $this->assertSame('assignment-instructions/aman.pdf', $assignment->fresh()->instruction_file_path);
        Storage::disk('local')->assertExists('assignment-instructions/aman.pdf');
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_migration_is_resumable_without_changing_existing_assignments(): void
    {
        $migration = require database_path('migrations/2026_10_18_010000_add_instruction_file_to_assignments_table.php');
        $migration->down();
        $context = $this->context();
        $assignment = $context['course']->assignments()->create([
            'meeting_id' => $context['meeting']->id,
            'title' => 'Tugas Lama',
            'type' => Assignment::TYPE_TUGAS,
            'max_score' => 100,
        ]);

        $migration->up();
        $migration->up();

        $this->assertSame('Tugas Lama', $assignment->fresh()->title);
        $this->assertNull($assignment->fresh()->instruction_file_path);
        $this->assertTrue(Schema::hasColumns('assignments', [
            'instruction_file_path', 'instruction_file_name', 'instruction_file_mime', 'instruction_file_size',
        ]));
    }
}
