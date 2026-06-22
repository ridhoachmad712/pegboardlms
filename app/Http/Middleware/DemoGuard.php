<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Saat MODE DEMO aktif, blokir aksi sensitif yang bisa merusak pengalaman
 * pengunjung lain atau mengunci akun demo bersama:
 *  - ganti kata sandi/email profil  → bisa mengunci akun demo 1-klik
 *  - ubah identitas/branding & API key AI → persisten & berisiko disalahgunakan
 *  - backup/unduh database → membuka seluruh data
 *  - reset/hapus akun mahasiswa massal
 *
 * Aksi membuat/menghapus kelas, tugas, dsb. TIDAK diblokir — itu inti yang
 * ingin dicoba calon pembeli, dan tetap aman karena data direset berkala.
 */
class DemoGuard
{
    private const BLOCKED = [
        'profile.update',
        'admin.settings.update',
        'admin.ai.update',
        'admin.backups.run',
        'admin.backups.download',
        'admin.students.destroy',
        'admin.students.bulkDestroy',
        'admin.students.bulkReset',
        'admin.students.resetPassword',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (config('demo.enabled')) {
            $name = $request->route()?->getName();

            if ($name && in_array($name, self::BLOCKED, true)) {
                return back()->with('error', 'Aksi ini dinonaktifkan dalam mode demo.');
            }
        }

        return $next($request);
    }
}
