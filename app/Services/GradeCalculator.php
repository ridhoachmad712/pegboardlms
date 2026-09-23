<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentGroup;
use App\Models\Course;
use App\Models\GradeScore;
use App\Models\Submission;
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

        // Persentase kehadiran per mahasiswa (bila ada komponen bertipe kehadiran).
        $attendancePercents = [];
        if ($components->contains(fn ($c) => $c->isAttendance())) {
            $grid = (new AttendanceService)->gridForCourse($course);
            foreach ($grid['summary'] as $uid => $s) {
                $attendancePercents[$uid] = $s['percent']; // null bila belum ada sesi
            }
        }

        // semua submission bernilai untuk kelas ini
        $submissions = Submission::whereIn('assignment_id', $assignments->pluck('id'))
            ->whereNotNull('score')
            ->get()
            ->groupBy('user_id');

        // Tugas kelompok: pengumpulan bersama dipetakan ke SEMUA anggota → [assignment_id][user_id] => submission.
        $groupSubs = [];
        $groupAssignmentIds = $assignments->where('mode', Assignment::MODE_KELOMPOK)->pluck('id');
        if ($groupAssignmentIds->isNotEmpty()) {
            $groups = AssignmentGroup::whereIn('assignment_id', $groupAssignmentIds)
                ->with(['members:id', 'submission'])
                ->get();
            foreach ($groups as $g) {
                if ($g->submission && ! is_null($g->submission->score)) {
                    foreach ($g->members as $m) {
                        $groupSubs[$g->assignment_id][$m->id] = $g->submission;
                    }
                }
            }
        }

        // Nilai manual (untuk komponen tanpa tugas online) → [component_id][user_id]
        $manual = GradeScore::whereIn('grade_component_id', $components->pluck('id'))
            ->whereNotNull('score')
            ->get()
            ->groupBy('grade_component_id')
            ->map(fn ($g) => $g->keyBy('user_id'));

        $rows = $students->map(function (User $student) use ($components, $assignmentsByComponent, $submissions, $groupSubs, $manual, $attendancePercents) {
            $studentSubs = ($submissions->get($student->id) ?? collect())->keyBy('assignment_id');
            $componentScores = [];
            $overrides = [];
            $final = 0.0;

            foreach ($components as $component) {
                $compAssignments = $assignmentsByComponent->get($component->id) ?? collect();
                $override = $manual->get($component->id)?->get($student->id);
                $isOverride = false;

                if ($override) {
                    // Nilai manual dosen SELALU diutamakan — termasuk override atas
                    // komponen otomatis (dosen tetap berkuasa mengedit).
                    $score = (float) $override->score;
                    $isOverride = true;
                } elseif ($component->isAttendance()) {
                    // Otomatis dari persentase kehadiran; 0 bila belum ada sesi.
                    $percent = $attendancePercents[$student->id] ?? null;
                    $score = round((float) ($percent ?? 0), 2);
                } elseif ($compAssignments->isNotEmpty()) {
                    // Otomatis dari tugas/kuis yang ditautkan (belum dikumpulkan = 0).
                    $percents = [];
                    $pendingExternalScore = false;
                    foreach ($compAssignments as $a) {
                        $sub = $studentSubs->get($a->id) ?? ($groupSubs[$a->id][$student->id] ?? null);
                        if ($a->isExternalQuiz() && (! $sub || is_null($sub->score))) {
                            // Ketiadaan hasil kuis eksternal berarti dosen belum menginput nilai,
                            // bukan otomatis nilai nol.
                            $pendingExternalScore = true;

                            continue;
                        }
                        $percents[] = ($sub && $a->max_score > 0)
                            ? (float) $sub->score / $a->max_score * 100
                            : 0.0;
                    }
                    $score = $pendingExternalScore
                        ? null
                        : round(array_sum($percents) / count($percents), 2);
                } else {
                    $score = null; // komponen manual, belum diisi
                }

                $componentScores[$component->id] = $score;
                $overrides[$component->id] = $isOverride;
                $final += ($score ?? 0) * $component->weight / 100;
            }

            $final = round($final, 2);

            return [
                'student' => $student,
                'components' => $componentScores,
                'overrides' => $overrides,
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

        // ID komponen yang nilainya otomatis (dari tugas atau kehadiran); sisanya = input manual
        $autoComponentIds = $assignmentsByComponent->keys()->map(fn ($k) => (int) $k)->all();
        foreach ($components as $c) {
            if ($c->isAttendance()) {
                $autoComponentIds[] = (int) $c->id;
            }
        }
        $autoComponentIds = array_values(array_unique($autoComponentIds));

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
