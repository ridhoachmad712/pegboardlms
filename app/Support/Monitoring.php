<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Http\Request;

/**
 * Mode pantau: admin boleh MELIHAT semua kelas (permintaan GET/HEAD) tanpa menjadi
 * pemiliknya, tetapi tidak boleh mengubah apa pun. Semua aksi tulis tetap ditolak
 * oleh cek pemilik/peserta yang biasa.
 */
class Monitoring
{
    public static function allows(Request $request, Course $course): bool
    {
        $user = $request->user();

        if (! $user?->isAdmin() || $course->user_id === $user->id || ! $request->isMethodSafe()) {
            return false;
        }

        // Tandai request agar layout menampilkan banner & menonaktifkan kontrol ubah.
        $request->attributes->set('monitoring', true);

        return true;
    }

    public static function active(?Request $request = null): bool
    {
        return (bool) ($request ?? request())->attributes->get('monitoring', false);
    }
}
