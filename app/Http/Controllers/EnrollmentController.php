<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    /** Halaman mahasiswa gabung kelas via kode. */
    public function showJoin(): View
    {
        return view('courses.join');
    }

    /** Mahasiswa gabung kelas dengan kode. */
    public function join(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isMahasiswa(), 403);

        $data = $request->validate(['join_code' => ['required', 'string', 'max:12']]);
        $code = strtoupper(trim($data['join_code']));

        $course = Course::where('join_code', $code)
            ->where('status', Course::STATUS_ACTIVE)
            ->first();

        if (! $course) {
            throw ValidationException::withMessages(['join_code' => 'Kode kelas tidak valid atau kelas tidak aktif.']);
        }

        if ($course->students()->whereKey($request->user()->id)->exists()) {
            return redirect()->route('courses.show', $course)->with('status', 'Anda sudah terdaftar di kelas ini.');
        }

        $course->students()->attach($request->user()->id, ['enrolled_at' => now()]);

        return redirect()->route('courses.show', $course)->with('status', 'Berhasil gabung ke '.$course->name.'.');
    }

    /** Dosen reset kata sandi mahasiswa (ke NIM, atau "password" bila NIM kosong). */
    public function resetPassword(Request $request, Course $course, User $user): RedirectResponse
    {
        $this->authorizeOwner($request, $course);
        abort_unless($course->students()->whereKey($user->id)->exists(), 404);

        $new = $user->nim_nip ?: 'password';
        $user->update(['password' => Hash::make($new)]);

        return back()->with('status', "Kata sandi {$user->name} direset menjadi: {$new}");
    }

    /** Unduh template CSV impor mahasiswa. */
    public function template(): Response
    {
        $csv = "nama,email,nim\nBudi Santoso,budi@contoh.ac.id,2109010001\nSiti Aminah,siti@contoh.ac.id,2109010002\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template-mahasiswa.csv"',
        ]);
    }

    /** Enroll satu/beberapa mahasiswa terdaftar ke kelas. */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // Hanya mahasiswa yang boleh di-enroll
        $mahasiswaIds = User::whereIn('id', $validated['user_ids'])
            ->where('role', User::ROLE_MAHASISWA)
            ->pluck('id')
            ->all();

        $payload = collect($mahasiswaIds)
            ->mapWithKeys(fn ($id) => [$id => ['enrolled_at' => now()]])
            ->all();

        $changes = $course->students()->syncWithoutDetaching($payload);

        $added = count($changes['attached']);

        return back()->with('status', "$added mahasiswa berhasil ditambahkan ke kelas.");
    }

    /** Import mahasiswa via CSV (kolom: nama,email,nim). Akun dibuat bila belum ada. */
    public function import(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Gagal membaca berkas CSV.');
        }

        $created = 0;
        $enrolled = 0;
        $skipped = 0;
        $row = 0;

        while (($cols = fgetcsv($handle)) !== false) {
            $row++;

            // Lewati header bila baris pertama mengandung "email"
            if ($row === 1 && stripos(implode(',', $cols), 'email') !== false) {
                continue;
            }

            $name = trim($cols[0] ?? '');
            $email = strtolower(trim($cols[1] ?? ''));
            $nim = trim($cols[2] ?? '');

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email,
                    'nim_nip' => $nim !== '' ? $nim : null,
                    'role' => User::ROLE_MAHASISWA,
                    'password' => Hash::make($nim !== '' ? $nim : 'password'),
                ]);
                $created++;
            }

            if ($user->role !== User::ROLE_MAHASISWA) {
                $skipped++;
                continue;
            }

            if (! $course->students()->whereKey($user->id)->exists()) {
                $course->students()->attach($user->id, ['enrolled_at' => now()]);
                $enrolled++;
            }
        }

        fclose($handle);

        return back()->with('status',
            "Import selesai: $enrolled mahasiswa di-enroll ($created akun baru, $skipped baris dilewati).");
    }

    public function destroy(Request $request, Course $course, User $user): RedirectResponse
    {
        $this->authorizeOwner($request, $course);

        $course->students()->detach($user->id);

        return back()->with('status', 'Mahasiswa dikeluarkan dari kelas.');
    }

    private function authorizeOwner(Request $request, Course $course): void
    {
        abort_unless($course->user_id === $request->user()->id, 403);

        if (! $request->isMethodSafe() && $course->isCompleted()) {
            abort(403, 'Kelas ini sudah selesai (read-only). Buka kembali untuk mengubah.');
        }
    }
}
