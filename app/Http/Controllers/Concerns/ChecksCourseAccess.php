<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use App\Support\Monitoring;
use Illuminate\Http\Request;

trait ChecksCourseAccess
{
    /** Dosen pemilik atau mahasiswa terdaftar boleh mengakses kelas; admin boleh melihat (mode pantau). */
    protected function ensureCourseAccess(Request $request, Course $course): void
    {
        $user = $request->user();

        if ($user->isDosen() && $course->user_id === $user->id) {
            return;
        }

        if ($user->isMahasiswa() && $course->students()->whereKey($user->id)->exists()) {
            return;
        }

        if (Monitoring::allows($request, $course)) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    /** Hanya dosen pemilik kelas; admin boleh melihat halaman dosen tanpa mengubah (mode pantau). */
    protected function ensureCourseOwner(Request $request, Course $course): void
    {
        if (Monitoring::allows($request, $course)) {
            return;
        }

        abort_unless(
            $request->user()->isDosen() && $course->user_id === $request->user()->id,
            403
        );

        if (! $request->isMethodSafe() && $course->isCompleted()) {
            abort(403, 'Kelas ini sudah selesai (read-only). Buka kembali untuk mengubah.');
        }
    }
}
