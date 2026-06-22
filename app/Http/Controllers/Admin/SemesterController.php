<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Setting;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SemesterController extends Controller
{
    /** Urutan semester dalam satu tahun (untuk pengurutan periode). */
    private const SEM_ORDER = ['Antara' => 1, 'Genap' => 2, 'Ganjil' => 3];

    /** Daftar semester terkelola, digabung dengan periode kelas yang ada. */
    public function index(): View
    {
        // Statistik kelas per periode (semua dosen), termasuk hitung mahasiswa unik.
        $courses = Course::query()->with('students:id')->get();
        $byPeriod = $courses->groupBy(fn ($c) => $c->year.'-'.$c->semester);

        // Semester yang dikelola admin (punya id → bisa dihapus dari daftar).
        $managed = Semester::all()->keyBy(fn ($s) => $s->year.'-'.$s->semester);

        // Gabungkan kunci dari tabel semester + periode kelas (agar tidak ada yang hilang).
        $keys = $managed->keys()->merge($byPeriod->keys())->unique();

        $periods = $keys->map(function ($key) use ($managed, $byPeriod) {
            [$year, $semester] = explode('-', $key, 2);
            $group = $byPeriod->get($key, collect());

            return (object) [
                'id' => $managed->get($key)?->id,
                'year' => (int) $year,
                'semester' => $semester,
                'key' => $key,
                'label' => $semester.' '.$year,
                'courses_count' => $group->count(),
                'lecturers_count' => $group->pluck('user_id')->unique()->count(),
                'students_count' => $group->pluck('students')->flatten()->pluck('id')->unique()->count(),
                'sort' => (int) $year * 10 + (self::SEM_ORDER[$semester] ?? 0),
            ];
        })->sortByDesc('sort')->values();

        $academicYear = Setting::get('academic_year', (string) date('Y'));
        $semester = Setting::get('semester', 'Ganjil');
        $activePeriod = ((int) $academicYear).'-'.$semester;

        return view('admin.semesters.index', compact('periods', 'activePeriod', 'academicYear', 'semester'));
    }

    /** Tambah semester baru ke daftar. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'in:Ganjil,Genap,Antara'],
        ]);

        if (Semester::where('year', $data['year'])->where('semester', $data['semester'])->exists()) {
            return back()->with('error', "Semester {$data['semester']} {$data['year']} sudah ada di daftar.");
        }

        Semester::create($data);
        Activity::log('create', "Menambahkan semester {$data['semester']} {$data['year']}");

        return back()->with('status', "Semester {$data['semester']} {$data['year']} ditambahkan.");
    }

    /** Tetapkan satu periode sebagai semester aktif (disimpan ke pengaturan). */
    public function updateActive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'in:Ganjil,Genap,Antara'],
        ]);

        Setting::put('academic_year', (string) $data['academic_year']);
        Setting::put('semester', $data['semester']);

        return back()->with('status', "Semester aktif disetel ke {$data['semester']} {$data['academic_year']}.");
    }

    /** Hapus semester dari daftar — ditolak jika masih ada kelas di periode itu. */
    public function destroy(Semester $semester): RedirectResponse
    {
        $count = Course::where('year', $semester->year)->where('semester', $semester->semester)->count();

        if ($count > 0) {
            return back()->with('error', "Tidak bisa menghapus {$semester->label()} — masih ada {$count} kelas di periode ini. Pindahkan atau hapus kelasnya terlebih dahulu.");
        }

        $label = $semester->label();
        $semester->delete();
        Activity::log('delete', "Menghapus semester {$label}");

        return back()->with('status', "Semester {$label} dihapus dari daftar.");
    }
}
