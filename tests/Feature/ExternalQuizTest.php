<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\GradeComponent;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\GradeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalQuizTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $lecturer = User::factory()->activeLecturer()->create();
        $student = User::factory()->create();
        $course = Course::create([
            'user_id' => $lecturer->id,
            'name' => 'Statistika',
            'code' => 'STA101',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $meeting = $course->meetings()->create([
            'number' => 1,
            'topic' => 'Peluang',
            'date' => today(),
        ]);
        $component = $course->gradeComponents()->create([
            'name' => 'Quiz',
            'type' => 'kuis',
            'weight' => 20,
        ]);

        return compact('lecturer', 'student', 'course', 'meeting', 'component');
    }

    private function payload(array $context, array $overrides = []): array
    {
        return array_merge([
            'meeting_id' => $context['meeting']->id,
            'title' => 'Kuis Wayground Bab 1',
            'description' => 'Kerjakan melalui Wayground.',
            'type' => Assignment::TYPE_KUIS,
            'delivery_mode' => Assignment::DELIVERY_EXTERNAL,
            'external_url' => 'https://wayground.com/join/example',
            'deadline' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_score' => 100,
            'grade_component_id' => $context['component']->id,
            'duration_minutes' => 30,
        ], $overrides);
    }

    public function test_lecturer_can_create_external_quiz(): void
    {
        $context = $this->context();

        $response = $this->actingAs($context['lecturer'])
            ->post(route('assignments.store', $context['course']), $this->payload($context));

        $quiz = Assignment::where('title', 'Kuis Wayground Bab 1')->firstOrFail();
        $response->assertRedirect(route('assignments.show', $quiz));
        $this->assertTrue($quiz->isExternalQuiz());
        $this->assertNull($quiz->duration_minutes);
        $this->assertSame('https://wayground.com/join/example', $quiz->external_url);
    }

    public function test_external_quiz_is_visible_to_student_but_cannot_be_taken_in_lms(): void
    {
        $context = $this->context();
        $quiz = $context['course']->assignments()->create([
            'meeting_id' => $context['meeting']->id,
            'grade_component_id' => $context['component']->id,
            'title' => 'Kuis Wayground',
            'type' => Assignment::TYPE_KUIS,
            'delivery_mode' => Assignment::DELIVERY_EXTERNAL,
            'external_url' => 'https://wayground.com/join/example',
            'max_score' => 100,
            'published' => true,
        ]);

        $this->actingAs($context['student'])->get(route('assignments.show', $quiz))
            ->assertOk()
            ->assertSee('Kuis eksternal')
            ->assertSee('https://wayground.com/join/example');

        $this->actingAs($context['student'])->get(route('quizzes.take', $quiz))->assertNotFound();
        $this->actingAs($context['lecturer'])->get(route('quizzes.questions', $quiz))->assertNotFound();
    }

    public function test_lecturer_can_save_external_scores_and_they_feed_grade_calculation(): void
    {
        $context = $this->context();
        $quiz = $context['course']->assignments()->create([
            'meeting_id' => $context['meeting']->id,
            'grade_component_id' => $context['component']->id,
            'title' => 'Kuis Wayground',
            'type' => Assignment::TYPE_KUIS,
            'delivery_mode' => Assignment::DELIVERY_EXTERNAL,
            'external_url' => 'https://wayground.com/join/example',
            'max_score' => 50,
            'published' => true,
        ]);

        $pendingRow = (new GradeCalculator)->forCourse($context['course'])['rows']->first();
        $this->assertNull($pendingRow['components'][$context['component']->id]);

        $this->actingAs($context['lecturer'])
            ->post(route('quizzes.externalScores', $quiz), [
                'scores' => [$context['student']->id => 40],
            ])->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $quiz->id,
            'user_id' => $context['student']->id,
            'score' => 40,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $context['student']->id,
            'type' => 'grade',
            'title' => 'Nilai kuis tersedia',
        ]);

        $row = (new GradeCalculator)->forCourse($context['course'])['rows']->first();
        $this->assertSame(80.0, $row['components'][$context['component']->id]);
        $this->assertSame(16.0, $row['final']);

        $this->actingAs($context['lecturer'])
            ->post(route('quizzes.externalScores', $quiz), [
                'scores' => [$context['student']->id => ''],
            ])->assertRedirect();
        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $quiz->id,
            'user_id' => $context['student']->id,
        ]);
    }

    public function test_external_scores_are_limited_to_enrolled_students_and_quiz_maximum(): void
    {
        $context = $this->context();
        $outsider = User::factory()->create();
        $otherLecturer = User::factory()->activeLecturer()->create();
        $quiz = $context['course']->assignments()->create([
            'meeting_id' => $context['meeting']->id,
            'title' => 'Kuis Eksternal',
            'type' => Assignment::TYPE_KUIS,
            'delivery_mode' => Assignment::DELIVERY_EXTERNAL,
            'external_url' => 'https://example.com/quiz',
            'max_score' => 25,
        ]);

        $this->actingAs($context['lecturer'])
            ->post(route('quizzes.externalScores', $quiz), [
                'scores' => [$context['student']->id => 26],
            ])->assertSessionHasErrors('scores.'.$context['student']->id);

        $this->actingAs($context['lecturer'])
            ->post(route('quizzes.externalScores', $quiz), [
                'scores' => [$outsider->id => 20],
            ])->assertRedirect();

        $this->actingAs($otherLecturer)
            ->post(route('quizzes.externalScores', $quiz), [
                'scores' => [$context['student']->id => 20],
            ])->assertForbidden();

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $quiz->id,
            'user_id' => $outsider->id,
        ]);
    }

    public function test_delivery_mode_cannot_change_after_quiz_activity_exists(): void
    {
        $context = $this->context();
        $quiz = $context['course']->assignments()->create([
            'meeting_id' => $context['meeting']->id,
            'title' => 'Kuis Internal',
            'type' => Assignment::TYPE_KUIS,
            'delivery_mode' => Assignment::DELIVERY_INTERNAL,
            'max_score' => 100,
        ]);
        QuizQuestion::create([
            'assignment_id' => $quiz->id,
            'question' => 'Berapa hasil 1 + 1?',
            'type' => 'pg',
            'options' => ['A' => '1', 'B' => '2'],
            'correct_answer' => 'B',
            'points' => 1,
        ]);

        $this->actingAs($context['lecturer'])
            ->put(route('assignments.update', $quiz), $this->payload($context))
            ->assertSessionHasErrors('delivery_mode');

        $this->assertTrue($quiz->fresh()->isInternalQuiz());
    }

    public function test_grade_component_must_belong_to_the_same_course(): void
    {
        $context = $this->context();
        $otherCourse = Course::create([
            'user_id' => $context['lecturer']->id,
            'name' => 'Kelas Lain',
            'code' => 'OTHER',
            'semester' => 'Ganjil',
            'year' => 2026,
            'status' => Course::STATUS_ACTIVE,
            'join_code' => Course::generateJoinCode(),
        ]);
        $otherComponent = GradeComponent::create([
            'course_id' => $otherCourse->id,
            'name' => 'Quiz',
            'type' => 'kuis',
            'weight' => 10,
        ]);

        $this->actingAs($context['lecturer'])
            ->post(route('assignments.store', $context['course']), $this->payload($context, [
                'grade_component_id' => $otherComponent->id,
            ]))->assertSessionHasErrors('grade_component_id');
    }
}
