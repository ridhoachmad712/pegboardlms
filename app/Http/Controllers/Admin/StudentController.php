<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $courseId = $request->integer('course') ?: null;

        $students = User::where('role', User::ROLE_MAHASISWA)
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('name', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%")
                ->orWhere('nim_nip', 'like', "%$q%")))
            ->when($courseId, fn ($x) => $x->whereHas('enrolledCourses', fn ($c) => $c->where('courses.id', $courseId)))
            ->withCount('enrolledCourses')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $courses = \App\Models\Course::orderBy('name')->get(['id', 'name']);

        return view('admin.students.index', compact('students', 'q', 'courses', 'courseId'));
    }

    public function create(): View
    {
        return view('admin.students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nim_nip' => ['nullable', 'string', 'max:50', 'unique:users,nim_nip'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'nim_nip' => $data['nim_nip'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => User::ROLE_MAHASISWA,
            'password' => Hash::make(($data['password'] ?? null) ?: ($data['nim_nip'] ?? null ?: 'password')),
        ]);

        return redirect()->route('admin.students.index')->with('status', 'Akun mahasiswa dibuat.');
    }

    public function edit(User $student): View
    {
        abort_unless($student->isMahasiswa(), 404);

        return view('admin.students.edit', ['student' => $student]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isMahasiswa(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'nim_nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nim_nip')->ignore($student->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $student->update($data);

        return redirect()->route('admin.students.index')->with('status', 'Data mahasiswa diperbarui.');
    }

    public function resetPassword(User $student): RedirectResponse
    {
        abort_unless($student->isMahasiswa(), 404);
        $new = $student->nim_nip ?: 'password';
        $student->update(['password' => Hash::make($new)]);

        return back()->with('status', "Kata sandi {$student->name} direset menjadi: {$new}");
    }

    /** Validasi & ambil ID mahasiswa terpilih dari request. */
    private function selectedStudentIds(Request $request): array
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return User::where('role', User::ROLE_MAHASISWA)
            ->whereIn('id', $data['ids'])
            ->pluck('id')
            ->all();
    }

    public function bulkResetPassword(Request $request): RedirectResponse
    {
        $students = User::whereIn('id', $this->selectedStudentIds($request))->get();
        foreach ($students as $student) {
            $student->update(['password' => Hash::make($student->nim_nip ?: 'password')]);
        }

        return back()->with('status', $students->count().' kata sandi direset menjadi NIM masing-masing (mahasiswa tanpa NIM → "password").');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->selectedStudentIds($request);
        $count = User::whereIn('id', $ids)->delete();

        return back()->with('status', "$count akun mahasiswa dihapus.");
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->isMahasiswa(), 404);
        $student->delete();

        return back()->with('status', 'Akun mahasiswa dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Gagal membaca berkas CSV.');
        }

        $created = 0;
        $skipped = 0;
        $row = 0;
        while (($cols = fgetcsv($handle)) !== false) {
            $row++;
            if ($row === 1 && stripos(implode(',', $cols), 'email') !== false) {
                continue;
            }
            $name = trim($cols[0] ?? '');
            $email = strtolower(trim($cols[1] ?? ''));
            $nim = trim($cols[2] ?? '');

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            User::create([
                'name' => $name !== '' ? $name : $email,
                'email' => $email,
                'nim_nip' => $nim !== '' ? $nim : null,
                'role' => User::ROLE_MAHASISWA,
                'password' => Hash::make($nim !== '' ? $nim : 'password'),
            ]);
            $created++;
        }
        fclose($handle);

        return back()->with('status', "Import selesai: $created akun dibuat, $skipped dilewati.");
    }
}
