<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Attendance;
use App\Models\Course;
use App\Services\AttendanceService;
use App\Services\GradeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    use ChecksCourseAccess;

    public function index(Request $request, Course $course): View
    {
        $this->ensureCourseOwner($request, $course);

        $data = $this->build($course);

        return view('analytics.index', ['course' => $course] + $data);
    }

    /** Endpoint JSON untuk Chart.js. */
    public function data(Request $request, Course $course): JsonResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = $this->build($course);

        return response()->json([
            'distribution' => $data['distribution'],
            'attendanceTrend' => $data['attendanceTrend'],
            'summary' => $data['summary'],
        ]);
    }

    private function build(Course $course): array
    {
        $grades = (new GradeCalculator())->forCourse($course);
        $attendance = (new AttendanceService())->gridForCourse($course);

        // Distribusi nilai akhir (bins)
        $bins = ['0-59' => 0, '60-69' => 0, '70-79' => 0, '80-89' => 0, '90-100' => 0];
        $finals = [];
        foreach ($grades['rows'] as $row) {
            $f = (float) $row['final'];
            $finals[] = $f;
            $key = match (true) {
                $f < 60 => '0-59',
                $f < 70 => '60-69',
                $f < 80 => '70-79',
                $f < 90 => '80-89',
                default => '90-100',
            };
            $bins[$key]++;
        }

        // Tren kehadiran per pertemuan (% hadir dari jumlah mahasiswa)
        $totalStudents = $attendance['students']->count();
        $trendLabels = [];
        $trendValues = [];
        foreach ($attendance['meetings'] as $m) {
            $hasSession = collect($attendance['matrix'])->contains(fn ($row) => isset($row[$m->id]));
            if (! $hasSession) {
                continue;
            }
            $hadir = Attendance::where('meeting_id', $m->id)->where('status', 'hadir')->count();
            $trendLabels[] = 'P'.$m->number;
            $trendValues[] = $totalStudents > 0 ? round($hadir / $totalStudents * 100, 1) : 0;
        }

        // Mahasiswa berisiko: nilai < 60 ATAU kehadiran < 75%
        $risk = collect();
        foreach ($grades['rows'] as $row) {
            $sid = $row['student']->id;
            $att = $attendance['summary'][$sid]['percent'] ?? null;
            $lowGrade = $row['final'] < 60;
            $lowAtt = ! is_null($att) && $att < 75;
            if ($lowGrade || $lowAtt) {
                $risk->push([
                    'student' => $row['student'],
                    'final' => $row['final'],
                    'letter' => $row['letter'],
                    'attendance' => $att,
                    'reasons' => array_filter([$lowGrade ? 'nilai' : null, $lowAtt ? 'kehadiran' : null]),
                ]);
            }
        }

        sort($finals);
        $n = count($finals);
        $median = $n === 0 ? 0 : ($n % 2 ? $finals[intdiv($n, 2)] : round(($finals[$n / 2 - 1] + $finals[$n / 2]) / 2, 2));

        $summary = [
            'count' => $n,
            'avg' => $grades['summary']['avg'],
            'median' => $median,
            'max' => $grades['summary']['max'],
            'min' => $grades['summary']['min'],
            'pass' => count(array_filter($finals, fn ($f) => $f >= 60)),
        ];

        return [
            'distribution' => ['labels' => array_keys($bins), 'values' => array_values($bins)],
            'attendanceTrend' => ['labels' => $trendLabels, 'values' => $trendValues],
            'risk' => $risk,
            'summary' => $summary,
        ];
    }
}
