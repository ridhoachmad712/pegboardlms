<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Support\Grades;
use Illuminate\Support\Collection;

class GradeCalculator
{
    /**
     * Hitung rekap nilai seluruh mahasiswa pada sebuah kelas.
     *
     * @return array{components: Collection, rows: Collection, summary: array}
     */
    public function forCourse(Course $course): array
    {
        $components = $course->gradeComponents()->orderBy('id')->get();

        // assignment_id => grade_component_id, max_score
        $assignments = $course->assignments()->whereNotNull('grade_component_id')->get();
        $assignmentsByComponent = $assignments->groupBy('grade_component_id');

        $students = $course->students()->orderBy('name')->get();

        // semua submission bernilai untuk kelas ini
        $submissions = \App\Models\Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->whereNotNull('score')
            ->get()
            ->groupBy('user_id');

        // Nilai manual (untuk komponen tanpa tugas online) → [component_id][user_id]
        $manual = \App\Models\GradeScore::whereIn('grade_component_id', $components->pluck('id'))
            ->whereNotNull('score')
            ->get()
            ->groupBy('grade_component_id')
            ->map(fn ($g) => $g->keyBy('user_id'));

        $rows = $students->map(function (User $student) use ($components, $assignmentsByComponent, $submissions, $manual) {
            $studentSubs = ($submissions->get($student->id) ?? collect())->keyBy('assignment_id');
            $componentScores = [];
            $final = 0.0;

            foreach ($components as $component) {
                $compAssignments = $assignmentsByComponent->get($component->id) ?? collect();

                if ($compAssignments->isNotEmpty()) {
                    // Otomatis dari tugas/kuis yang ditautkan
                    $percents = [];
                    foreach ($compAssignments as $a) {
                        if ($sub = $studentSubs->get($a->id)) {
                            $percents[] = $a->max_score > 0
                                ? (float) $sub->score / $a->max_score * 100
                                : 0;
                        }
                    }
                    $score = count($percents) ? round(array_sum($percents) / count($percents), 2) : null;
                } else {
                    // Nilai manual (skala 0–100)
                    $entry = $manual->get($component->id)?->get($student->id);
                    $score = $entry ? (float) $entry->score : null;
                }

                $componentScores[$component->id] = $score;
                $final += ($score ?? 0) * $component->weight / 100;
            }

            $final = round($final, 2);

            return [
                'student' => $student,
                'components' => $componentScores,
                'final' => $final,
                'letter' => Grades::letter($final),
            ];
        });

        $finals = $rows->pluck('final');
        $summary = [
            'count' => $rows->count(),
            'avg' => $finals->count() ? round($finals->avg(), 2) : 0,
            'max' => $finals->count() ? $finals->max() : 0,
            'min' => $finals->count() ? $finals->min() : 0,
            'weight_total' => (int) $components->sum('weight'),
        ];

        // ID komponen yang nilainya otomatis dari tugas (sisanya = input manual)
        $autoComponentIds = $assignmentsByComponent->keys()->map(fn ($k) => (int) $k)->all();

        return compact('components', 'rows', 'summary', 'autoComponentIds');
    }

    /** Nilai untuk satu mahasiswa (tampilan transparansi). */
    public function forStudent(Course $course, User $student): array
    {
        $data = $this->forCourse($course);
        $row = $data['rows']->firstWhere('student.id', $student->id);

        return [
            'components' => $data['components'],
            'row' => $row,
        ];
    }
}
