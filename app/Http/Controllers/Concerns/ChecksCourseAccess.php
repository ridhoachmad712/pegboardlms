<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use Illuminate\Http\Request;

trait ChecksCourseAccess
{
    /** Dosen pemilik atau mahasiswa terdaftar boleh mengakses kelas. */
    protected function ensureCourseAccess(Request $request, Course $course): void
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

    /** Hanya dosen pemilik kelas. */
    protected function ensureCourseOwner(Request $request, Course $course): void
    {
        abort_unless(
            $request->user()->isDosen() && $course->user_id === $request->user()->id,
            403
        );

        if (! $request->isMethodSafe() && $course->isCompleted()) {
            abort(403, 'Kelas ini sudah selesai (read-only). Buka kembali untuk mengubah.');
        }
    }
}
