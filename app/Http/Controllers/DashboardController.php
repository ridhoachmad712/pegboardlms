<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\Semester;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\StudentActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Arahkan ke dashboard sesuai role. */
    public function index(Request $request): RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route(
            $request->user()->isDosen() ? 'dashboard.dosen' : 'dashboard.mahasiswa'
        );
    }

    public function dosen(Request $request): View
    {
        $user = $request->user();
        $pendingLecturerCount = $user->isAdmin()
            ? User::where('role', User::ROLE_DOSEN)->whereNull('lecturer_activated_at')->count()
            : 0;

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

        $activeKeys = Semester::activeKeys();
        $periode = (string) $request->query('periode', 'active');

        // Semua statistik & daftar kelas mengikuti periode terpilih.
        // 'all' = semua; 'active' = gabungan semester aktif; selain itu = satu periode.
        $periodCourses = $courses;
        if ($periode === 'active') {
            $periodCourses = $courses->filter(fn ($c) => in_array($c->year.'-'.$c->semester, $activeKeys, true))->values();
        } elseif ($periode !== 'all' && str_contains($periode, '-')) {
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

        $activeLabel = count($activeKeys) === 1
            ? Semester::keyLabel($activeKeys[0])
            : 'Semester aktif ('.count($activeKeys).')';

        return view('dashboard.dosen', compact(
            'stats', 'activeCourses', 'periods', 'periode', 'activeKeys', 'activeLabel',
            'needGrading', 'needAttendance', 'todayMeetings', 'pendingLecturerCount'
        ));
    }

    public function mahasiswa(Request $request, AttendanceService $attendance, StudentActivity $activityService): View
    {
        $user = $request->user();

        $courses = $user->enrolledCourses()
            ->where('status', Course::STATUS_ACTIVE)
            ->with('lecturer')
            ->withCount('meetings')
            ->get();

        $courseIds = $courses->pluck('id');
        $submittedIds = $user->submissions()->pluck('assignment_id');

        // Tugas kelompok yang kelompoknya (kelompok si mahasiswa) sudah mengumpulkan
        // dianggap sudah dikumpulkan juga bagi mahasiswa ini.
        $groupSubmittedIds = Assignment::whereIn('course_id', $courseIds)
            ->where('mode', Assignment::MODE_KELOMPOK)
            ->whereHas('groups', fn ($g) => $g
                ->whereHas('members', fn ($m) => $m->whereKey($user->id))
                ->has('submission'))
            ->pluck('id');
        $submittedIds = $submittedIds->merge($groupSubmittedIds)->unique();

        // Tugas/kuis pending (belum dikumpulkan)
        $pending = Assignment::whereIn('course_id', $courseIds)
            ->where('published', true)
            ->whereNotIn('id', $submittedIds)
            ->with('course')
            ->orderByRaw('deadline IS NULL, deadline ASC')
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

        $activity = $activityService->forStudent($user);

        return view('dashboard.mahasiswa', compact(
            'courses', 'pending', 'upcomingMeetings', 'lowAttendance', 'stats', 'activity'
        ));
    }
}
