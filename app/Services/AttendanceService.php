<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Course;
use Illuminate\Support\Collection;

class AttendanceService
{
    /**
     * Grid kehadiran satu kelas: mahasiswa × pertemuan.
     *
     * @return array{meetings: Collection, students: Collection, matrix: array, summary: array, sessions: int}
     */
    public function gridForCourse(Course $course): array
    {
        $meetings = $course->meetings()->orderBy('number')->get();
        $students = $course->students()->orderBy('name')->get();

        $records = Attendance::whereIn('meeting_id', $meetings->pluck('id'))->get();

        // pertemuan yang sudah memiliki sesi absensi
        $sessionMeetingIds = $records->pluck('meeting_id')->unique();
        $sessions = $sessionMeetingIds->count();

        // matrix[user_id][meeting_id] = status
        $matrix = [];
        foreach ($records as $r) {
            $matrix[$r->user_id][$r->meeting_id] = $r->status;
        }

        $summary = [];
        foreach ($students as $s) {
            $hadir = 0;
            foreach ($sessionMeetingIds as $mid) {
                $status = $matrix[$s->id][$mid] ?? 'alpa';
                if ($status === 'hadir') {
                    $hadir++;
                }
            }
            $summary[$s->id] = [
                'hadir' => $hadir,
                'sessions' => $sessions,
                'percent' => $sessions > 0 ? round($hadir / $sessions * 100, 1) : null,
            ];
        }

        return compact('meetings', 'students', 'matrix', 'summary', 'sessions');
    }

    /** Persentase kehadiran satu mahasiswa di satu kelas. */
    public function studentPercent(Course $course, int $studentId): ?float
    {
        $grid = $this->gridForCourse($course);

        return $grid['summary'][$studentId]['percent'] ?? null;
    }
}
