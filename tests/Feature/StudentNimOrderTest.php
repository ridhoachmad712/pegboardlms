<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use App\Services\GradingQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNimOrderTest extends TestCase
{
    use RefreshDatabase;

    /** Nama sengaja berlawanan dengan NIM agar urutan abjad ≠ urutan NIM. */
    private const STUDENTS = [
        ['Zahra', '2109001'],
        ['Yusuf', '2109002'],
        ['Wawan', '2109010'],   // "2109010" > "2109002" secara angka
        ['Andi', null],         // tanpa NIM → paling akhir
    ];

    private const EXPECTED = ['Zahra', 'Yusuf', 'Wawan', 'Andi'];

    private function context(): array
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Akuntansi Dasar',
            'code' => 'AKD101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        // Didaftarkan acak agar urutan bukan kebetulan dari id.
        $students = [];
        foreach ([2, 0, 3, 1] as $i) {
            [$name, $nim] = self::STUDENTS[$i];
            $students[$name] = User::factory()->create(['name' => $name, 'nim_nip' => $nim]);
            $course->students()->attach($students[$name]->id, ['enrolled_at' => now()]);
        }
        $meeting = $course->meetings()->create(['number' => 1, 'topic' => 'Pendahuluan', 'date' => today()]);
        $assignment = $course->assignments()->create([
            'title' => 'Jurnal Umum',
            'type' => Assignment::TYPE_TUGAS,
            'submission_mode' => Assignment::SUBMISSION_TEXT,
            'mode' => Assignment::MODE_INDIVIDU,
            'max_score' => 100,
            'published' => true,
        ]);

        return compact('lecturer', 'course', 'meeting', 'assignment', 'students');
    }

    public function test_course_students_relation_and_comparator_order_by_nim(): void
    {
        $c = $this->context();

        $this->assertSame(self::EXPECTED, $c['course']->students()->pluck('name')->all());
        $this->assertSame(self::EXPECTED, $c['course']->fresh()->students->pluck('name')->all());
        $this->assertSame(
            self::EXPECTED,
            collect($c['students'])->shuffle()->sort(User::compareByNim(...))->pluck('name')->values()->all(),
        );
    }

    public function test_attendance_and_student_pages_list_by_nim(): void
    {
        $c = $this->context();
        $this->actingAs($c['lecturer']);

        $this->get(route('attendance.session', $c['meeting']))->assertOk()->assertSeeInOrder(self::EXPECTED);
        $this->get(route('attendance.index', $c['course']))->assertOk()->assertSeeInOrder(self::EXPECTED);
        $this->get(route('courses.students', $c['course']))->assertOk()->assertSeeInOrder(self::EXPECTED);
        $this->get(route('grades.index', $c['course']))->assertOk()->assertSeeInOrder(self::EXPECTED);
    }

    public function test_assignment_submissions_pending_and_grading_queue_follow_nim(): void
    {
        $c = $this->context();
        foreach (['Andi', 'Wawan', 'Zahra'] as $name) {
            Submission::create([
                'assignment_id' => $c['assignment']->id,
                'user_id' => $c['students'][$name]->id,
                'answer_text' => 'Jawaban',
                'submitted_at' => now(),
            ]);
        }

        $this->actingAs($c['lecturer'])
            ->get(route('assignments.show', $c['assignment']))
            ->assertOk()
            ->assertSeeInOrder(['Zahra', 'Wawan', 'Andi']);

        $this->assertSame(
            ['Zahra', 'Wawan', 'Andi'],
            GradingQueue::for($c['assignment'])->map(fn ($s) => $s->student->name)->all(),
        );
    }

    public function test_admin_student_list_orders_by_nim(): void
    {
        $this->context();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSeeInOrder(self::EXPECTED);
    }
}
