<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LecturerAccount
{
    public function setDisabled(User $lecturer, User $admin, bool $disabled): void
    {
        $this->authorize($admin);
        DB::transaction(function () use ($lecturer, $disabled) {
            $account = $this->lockedLecturer($lecturer);
            if ($account->isLecturerDisabled() === $disabled) {
                return;
            }
            $account->forceFill([
                'lecturer_disabled_at' => $disabled ? now() : null,
                // Re-enabling never revives a previously signed-in session.
                'lecturer_session_version' => $account->lecturer_session_version + 1,
                'remember_token' => Str::random(60),
            ])->save();
            if ($disabled) {
                $account->activationCodes()->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }
            Activity::log($disabled ? 'lecturer_disabled' : 'lecturer_enabled',
                ($disabled ? 'Menonaktifkan' : 'Mengaktifkan kembali').' akses dosen #'.$account->id.'.');
        });
    }

    /**
     * Aktivasi langsung oleh admin, tanpa kode. Idempoten: akun yang sudah aktif tidak berubah.
     */
    public function activate(User $lecturer, User $admin): void
    {
        $this->authorize($admin);
        DB::transaction(function () use ($lecturer) {
            $account = $this->lockedLecturer($lecturer);
            if (! $account->needsLecturerActivation()) {
                return;
            }
            $account->forceFill(['lecturer_activated_at' => now()])->save();
            // Tidak ada kode lagi; batalkan sisa kode lama yang belum terpakai bila ada.
            $account->activationCodes()->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            Activity::log('lecturer_activation', 'Admin mengaktifkan akses dosen #'.$account->id.' secara langsung.');
        });
    }

    public function resetPassword(User $lecturer, User $admin): string
    {
        $this->authorize($admin);

        return DB::transaction(function () use ($lecturer) {
            $account = $this->lockedLecturer($lecturer);
            $plain = Str::password(length: 20, symbols: false);
            $account->forceFill([
                'password' => $plain, // User's hashed cast handles storage.
                'must_change_password' => true,
                'lecturer_session_version' => $account->lecturer_session_version + 1,
                'remember_token' => Str::random(60),
            ])->save();
            Activity::log('lecturer_password_reset', 'Mereset kata sandi dosen #'.$account->id.' dan membatalkan sesi lama.');

            return $plain;
        });
    }

    /**
     * Hapus permanen akun dosen. Diblokir bila dosen masih memiliki kelas,
     * untuk mencegah kehilangan data mahasiswa (nilai, kehadiran, submission)
     * lewat cascade. Kode aktivasi & notifikasi ikut terhapus (cascade FK);
     * riwayat aktivitas dipertahankan (user_id di-null-kan).
     */
    public function delete(User $lecturer, User $admin): void
    {
        $this->authorize($admin);
        DB::transaction(function () use ($lecturer, $admin) {
            $account = $this->lockedLecturer($lecturer);
            if ($account->id === $admin->id) {
                throw ValidationException::withMessages(['lecturer' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
            }
            if ($account->teachingCourses()->exists()) {
                throw ValidationException::withMessages([
                    'lecturer' => 'Dosen ini masih memiliki kelas. Nonaktifkan akun, atau hapus/pindahkan kelasnya terlebih dahulu.',
                ]);
            }
            $avatar = $account->avatar;
            Activity::log('lecturer_deleted', 'Menghapus akun dosen #'.$account->id.' ('.$account->email.').');
            $account->delete();
            if ($avatar) {
                Storage::disk('public')->delete($avatar);
            }
        });
    }

    private function authorize(User $admin): void
    {
        abort_unless($admin->isAdmin() && ! $admin->needsLecturerActivation() && ! $admin->isLecturerDisabled(), 403);
        abort_if(config('demo.enabled'), 403);
    }

    private function lockedLecturer(User $lecturer): User
    {
        $account = User::whereKey($lecturer->id)->lockForUpdate()->firstOrFail();
        abort_unless($account->isDosen(), 404);
        abort_if($account->isAdmin(), 403, 'Akun admin tidak dapat diubah melalui menu dosen.');

        return $account;
    }
}
