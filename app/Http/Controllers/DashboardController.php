<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Material;
use App\Models\Meeting;
use App\Models\Setting;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Arahkan ke dashboard sesuai role. */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route(
            $request->user()->isDosen() ? 'dashboard.dosen' : 'dashboard.mahasiswa'
        );
    }

    public function dosen(Request $request): View
    {
        $user = $request->user();

        $courses = $user->teachingCourses()
            ->withCount(['students', 'meetings'])
            ->withCount(['submissions as ungraded_count' => fn ($q) => $q
                ->whereNotNull('submissions.submitted_at')
                ->whereNull('submissions.score')])
            ->latest()
            ->get();

        // Periode (semester + tahun): default semester aktif, bisa diganti via dropdown
        $semOrder = ['Antara' => 1, 'Genap' => 2, 'Ganjil' => 3];
        $periods = $courses->map(fn ($c) => (object) [
            'key' => $c->year.'-'.$c->semester,
            'label' => $c->semester.' '.$c->year,
            'sort' => $c->year * 10 + ($semOrder[$c->semester] ?? 0),
        ])->unique('key')->sortByDesc('sort')->values();

        $activePeriod = ((int) Setting::get('academic_year', date('Y'))).'-'.Setting::get('semester', 'Ganjil');
        $periode = (string) $request->query('periode', $activePeriod);

        // Semua statistik & daftar kelas mengikuti periode terpilih
        $periodCourses = $courses;
        if ($periode !== 'all' && str_contains($periode, '-')) {
            [$py, $ps] = explode('-', $periode, 2);
            $periodCourses = $courses->filter(fn ($c) => (string) $c->year === $py && $c->semester === $ps)->values();
        }

        $periodIds = $periodCourses->pluck('id');
        $activeCourses = $periodCourses->where('status', Course::STATUS_ACTIVE)->values();

        // ===== Pusat tindakan: hal yang perlu dikerjakan dosen (kelas aktif pada periode) =====
        $actionIds = $activeCourses->pluck('id');

        // Tugas/kuis dengan pengumpulan belum dinilai
        $needGrading = $actionIds->isEmpty() ? collect() : Assignment::whereIn('course_id', $actionIds)
            ->withCount(['submissions as ungraded_count' => fn ($q) => $q
                ->whereNotNull('submitted_at')->whereNull('score')])
            ->with('course')
            ->get()
            ->filter(fn ($a) => $a->ungraded_count > 0)
            ->sortByDesc('ungraded_count')
            ->values();

        // Pertemuan yang tanggalnya sudah tiba/lewat tapi belum ada sesi/kehadiran
        $needAttendance = $actionIds->isEmpty() ? collect() : Meeting::whereIn('course_id', $actionIds)
            ->whereNotNull('date')
            ->whereDate('date', '<=', today())
            ->whereDoesntHave('attendances')
            ->whereDoesntHave('tokens')
            ->with('course')
            ->orderBy('date')
            ->get();

        // Pertemuan hari ini (untuk quick action "Absensi Hari Ini")
        $todayMeetings = $actionIds->isEmpty() ? collect() : Meeting::whereIn('course_id', $actionIds)
            ->whereDate('date', today())
            ->with('course')
            ->orderBy('number')
            ->get();

        $stats = [
            'active_courses' => $activeCourses->count(),
            'subjects' => $activeCourses->unique('code')->count(),
            'students' => $periodIds->isEmpty() ? 0
                : Enrollment::whereIn('course_id', $periodIds)->distinct('user_id')->count('user_id'),
            'assignments' => $periodIds->isEmpty() ? 0
                : Assignment::whereIn('course_id', $periodIds)->count(),
        ];

        return view('dashboard.dosen', compact(
            'stats', 'activeCourses', 'periods', 'periode', 'activePeriod',
            'needGrading', 'needAttendance', 'todayMeetings'
        ));
    }

    public function mahasiswa(Request $request, AttendanceService $attendance): View
    {
        $user = $request->user();

        $courses = $user->enrolledCourses()
            ->where('status', Course::STATUS_ACTIVE)
            ->with('lecturer')
            ->withCount('meetings')
            ->get();

        $courseIds = $courses->pluck('id');
        $submittedIds = $user->submissions()->pluck('assignment_id');

        // Tugas/kuis pending (belum dikumpulkan)
        $pending = Assignment::whereIn('course_id', $courseIds)
            ->where('published', true)
            ->whereNotIn('id', $submittedIds)
            ->with('course')
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->get();

        // Nilai terbaru
        $recentGrades = $user->submissions()
            ->whereNotNull('score')
            ->with('assignment.course')
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Pertemuan mendatang
        $upcomingMeetings = Meeting::whereIn('course_id', $courseIds)
            ->whereDate('date', '>=', now()->toDateString())
            ->with('course')
            ->orderBy('date')
            ->take(5)
            ->get();

        // Kehadiran per kelas + alert <75%
        $lowAttendance = collect();
        $percents = [];
        foreach ($courses as $course) {
            $p = $attendance->studentPercent($course, $user->id);
            $percents[] = $p;
            if (! is_null($p) && $p < 75) {
                $lowAttendance->push(['course' => $course, 'percent' => $p]);
            }
        }
        $valid = array_filter($percents, fn ($p) => ! is_null($p));
        $avgAttendance = count($valid) ? round(array_sum($valid) / count($valid), 1) : null;

        $stats = [
            'courses' => $courses->count(),
            'pending' => $pending->count(),
            'attendance' => $avgAttendance,
            'unread' => $user->notifications()->unread()->count(),
        ];

        return view('dashboard.mahasiswa', compact(
            'courses', 'pending', 'recentGrades', 'upcomingMeetings', 'lowAttendance', 'stats'
        ));
    }
}
