<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Setting;
use App\Models\Syllabus;
use App\Models\User;
use App\Services\GradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    /** Daftar "Kelas Saya" — tampilan berbeda per role. */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isDosen()) {
            $filter = $request->query('status') === 'completed' ? 'completed' : 'active';

            // Daftar periode (semester + tahun) yang pernah diampu — untuk dropdown
            $semOrder = ['Antara' => 1, 'Genap' => 2, 'Ganjil' => 3];
            $periods = $user->teachingCourses()
                ->select('year', 'semester')->distinct()->get()
                ->map(fn ($c) => (object) [
                    'key' => $c->year.'-'.$c->semester,
                    'label' => $c->semester.' '.$c->year,
                    'sort' => $c->year * 10 + ($semOrder[$c->semester] ?? 0),
                ])
                ->sortByDesc('sort')->values();

            $activePeriod = ((int) Setting::get('academic_year', date('Y'))).'-'.Setting::get('semester', 'Ganjil');
            $periode = (string) $request->query('periode', $activePeriod); // default: semester aktif

            // Terapkan filter periode ke sebuah query (kecuali "Semua semester")
            $applyPeriode = function ($query) use ($periode) {
                if ($periode !== 'all' && str_contains($periode, '-')) {
                    [$y, $s] = explode('-', $periode, 2);
                    $query->where('year', (int) $y)->where('semester', $s);
                }

                return $query;
            };

            $counts = $applyPeriode($user->teachingCourses())
                ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
            $activeCount = (int) ($counts[Course::STATUS_ACTIVE] ?? 0);
            $completedCount = (int) ($counts[Course::STATUS_COMPLETED] ?? 0);

            $courses = $applyPeriode($user->teachingCourses())
                ->where('status', $filter === 'completed' ? Course::STATUS_COMPLETED : Course::STATUS_ACTIVE)
                ->withCount(['students', 'meetings'])
                ->withCount(['submissions as ungraded_count' => fn ($q) => $q
                    ->whereNotNull('submissions.submitted_at')
                    ->whereNull('submissions.score')])
                ->latest()
                ->get();

            return view('courses.index-dosen', compact(
                'courses', 'filter', 'activeCount', 'completedCount',
                'periods', 'periode', 'activePeriod'
            ));
        }

        $courses = $user->enrolledCourses()
            ->where('status', Course::STATUS_ACTIVE)
            ->with('lecturer')
            ->withCount('meetings')
            ->get();

        return view('courses.index-mahasiswa', compact('courses'));
    }

    public function create(): View
    {
        return view('courses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['user_id'] = $request->user()->id;
        $data['status'] = Course::STATUS_ACTIVE;
        $data['join_code'] = Course::generateJoinCode();

        $course = Course::create($data);
        $this->saveSyllabus($request, $course);

        return redirect()->route('courses.show', $course)
            ->with('status', 'Kelas berhasil dibuat.');
    }

    /** Simpan isian RPS (CPL/CPMK/Sub-CPMK/Deskripsi/Referensi) dari form kelas ke RPS mata kuliah. */
    private function saveSyllabus(Request $request, Course $course): void
    {
        $input = (array) $request->input('syllabus', []);

        // Field bernomor dikirim sebagai array baris → digabung jadi teks per baris.
        $syl = [
            'cpl' => Syllabus::listToText($input['cpl'] ?? null),
            'cpmk' => Syllabus::listToText($input['cpmk'] ?? null),
            'sub_cpmk' => Syllabus::listToText($input['sub_cpmk'] ?? null),
            'description' => Syllabus::listToText($input['description'] ?? null),
            'references' => Syllabus::listToText($input['references'] ?? null),
        ];

        // Jangan buat baris RPS kosong saat semua isian dibiarkan kosong & belum ada RPS.
        if (! $course->syllabus && collect($syl)->every(fn ($v) => blank($v))) {
            return;
        }

        $course->syllabus()->updateOrCreate(['course_id' => $course->id], $syl);
    }

    public function show(Request $request, Course $course, GradeCalculator $calc): View
    {
        $this->authorizeView($request, $course);

        $isDosen = $request->user()->isDosen();

        $course->load([
            'lecturer',
            'meetings.materials',
            // Mahasiswa hanya melihat tugas/kuis yang sudah dipublikasikan
            'meetings.assignments' => fn ($q) => $isDosen ? $q : $q->where('published', true),
        ]);
        $course->loadCount('students');

        $readiness = [];
        $myAttendance = collect();

        if ($isDosen) {
            $readiness = $this->completionReadiness($course, $calc);
        } else {
            // Presensi mahasiswa per pertemuan (untuk swa-presensi pertemuan Mandiri)
            $myAttendance = \App\Models\Attendance::where('user_id', $request->user()->id)
                ->whereIn('meeting_id', $course->meetings->pluck('id'))
                ->get()->keyBy('meeting_id');
        }

        return view('courses.show', compact('course', 'isDosen', 'readiness', 'myAttendance'));
    }

    /** Daftar mahasiswa kelas (tab tersendiri, khusus dosen pemilik). */
    public function students(Request $request, Course $course): View
    {
        $this->authorizeOwner($request, $course);

        $course->loadCount('students');
        $students = $course->students()->orderBy('name')->get();
        $availableStudents = User::where('role', User::ROLE_MAHASISWA)
            ->whereNotIn('id', $students->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'nim_nip']);

        return view('courses.students', compact('course', 'students', 'availableStudents'));
    }

    /** Cek kesiapan kelas untuk ditandai selesai (16 pertemuan + semua dinilai). */
    private function completionReadiness(Course $course, GradeCalculator $calc): array
    {
        $meetingsCount = $course->meetings->count();
        $enoughMeetings = $meetingsCount >= MeetingController::MAX_MEETINGS;

        $data = $calc->forCourse($course);
        $studentsCount = $course->students_count ?? $course->students()->count();
        $weightOk = $data['summary']['weight_total'] === 100 && $data['components']->isNotEmpty();

        $ungraded = 0;
        foreach ($data['rows'] as $row) {
            foreach ($row['components'] as $value) {
                if (is_null($value)) {
                    $ungraded++;
                    break;
                }
            }
        }

        $allGraded = $studentsCount > 0 && $weightOk && $ungraded === 0;

        return [
            'meetings' => $meetingsCount,
            'max_meetings' => MeetingController::MAX_MEETINGS,
            'enough_meetings' => $enoughMeetings,
            'all_graded' => $allGraded,
            'weight_ok' => $weightOk,
            'ungraded' => $ungraded,
            'students' => $studentsCount,
            'can_complete' => $enoughMeetings && $allGraded,
        ];
    }

    public function edit(Request $request, Course $course): View|RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        if ($course->isCompleted()) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'Kelas sudah selesai (read-only). Buka kembali untuk mengubah.');
        }

        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);
        abort_if($course->isCompleted(), 403, 'Kelas sudah selesai (read-only).');

        $course->update($this->validateData($request));
        $this->saveSyllabus($request, $course);

        return redirect()->route('courses.show', $course)
            ->with('status', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $course->delete(); // soft delete — masuk tong sampah, bisa dipulihkan

        return redirect()->route('courses.index')
            ->with('status', 'Kelas dipindahkan ke tong sampah. Bisa dipulihkan dari menu Tong Sampah.');
    }

    /** Daftar kelas yang dihapus (tong sampah) milik dosen. */
    public function trash(Request $request): View
    {
        $courses = $request->user()->teachingCourses()
            ->onlyTrashed()
            ->withCount(['students', 'meetings'])
            ->latest('deleted_at')
            ->get();

        return view('courses.trash', compact('courses'));
    }

    /** Pulihkan kelas dari tong sampah. */
    public function restore(Request $request, int $id): RedirectResponse
    {
        $course = $request->user()->teachingCourses()->onlyTrashed()->findOrFail($id);
        $course->restore();

        return redirect()->route('courses.show', $course)
            ->with('status', 'Kelas berhasil dipulihkan.');
    }

    /** Hapus permanen kelas beserta seluruh datanya. */
    public function forceDestroy(Request $request, int $id): RedirectResponse
    {
        $course = $request->user()->teachingCourses()->onlyTrashed()->findOrFail($id);
        $course->forceDelete();

        return redirect()->route('courses.trash')
            ->with('status', 'Kelas dihapus permanen.');
    }

    public function regenerateJoinCode(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);
        $course->update(['join_code' => Course::generateJoinCode()]);

        return back()->with('status', 'Kode gabung diperbarui.');
    }

    public function toggleComplete(Request $request, Course $course, GradeCalculator $calc): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        // Membuka kembali kelas selesai → selalu boleh
        if ($course->isCompleted()) {
            $course->update(['status' => Course::STATUS_ACTIVE]);

            return back()->with('status', 'Kelas dibuka kembali — sekarang bisa diubah.');
        }

        // Menandai selesai → gerbang ketat: 16 pertemuan + semua mahasiswa dinilai
        $course->load('meetings');
        $r = $this->completionReadiness($course, $calc);

        if (! $r['can_complete']) {
            $reasons = [];
            if (! $r['enough_meetings']) {
                $reasons[] = "pertemuan baru {$r['meetings']}/{$r['max_meetings']}";
            }
            if (! $r['all_graded']) {
                $reasons[] = ! $r['weight_ok']
                    ? 'komponen nilai belum lengkap (bobot harus 100%)'
                    : "{$r['ungraded']} mahasiswa belum dinilai lengkap";
            }

            return back()->with('error', 'Belum bisa diselesaikan: '.implode(' & ', $reasons).'.');
        }

        $course->update(['status' => Course::STATUS_COMPLETED]);

        return back()->with('status', 'Kelas ditandai selesai. Sekarang hanya bisa dilihat (read-only).');
    }

    // --- Helpers ---

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'semester' => ['required', 'in:Ganjil,Genap,Antara'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'default_meeting_type' => ['required', 'in:tatap_muka,mandiri'],
            'description' => ['nullable', 'string'],
        ]);
    }

    /** Dosen pemilik boleh akses; mahasiswa harus terdaftar. */
    private function authorizeView(Request $request, Course $course): void
    {
        $user = $request->user();

        if ($user->isDosen() && $course->user_id === $user->id) {
            return;
        }

        if ($user->isMahasiswa() && $course->students()->whereKey($user->id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    private function authorizeOwner(Request $request, Course $course): void
    {
        abort_unless($course->user_id === $request->user()->id, 403);
    }
}
