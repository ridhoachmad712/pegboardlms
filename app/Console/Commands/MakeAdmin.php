<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Activity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeAdmin extends Command
{
    protected $signature = 'lms:make-admin {email : Email akun pemilik instalasi}
        {--promote-student : Izinkan akun mahasiswa yang disebutkan beralih menjadi dosen/admin}';

    protected $description = 'Tetapkan akun yang sudah ada sebagai admin aktif tanpa mengganti password';

    public function handle(): int
    {
        if (! Schema::hasColumns('users', ['is_admin', 'lecturer_activated_at'])) {
            $this->error('Jalankan php artisan migrate --force terlebih dahulu.');

            return self::FAILURE;
        }

        $email = strtolower(trim($this->argument('email')));

        return DB::transaction(function () use ($email): int {
            $matches = User::whereRaw('LOWER(email) = ?', [$email])->limit(2)->lockForUpdate()->get();
            if ($matches->count() !== 1) {
                $this->error('Email harus cocok dengan tepat satu akun terdaftar. Tidak ada akun yang diubah.');

                return self::FAILURE;
            }

            $user = $matches->first();
            if ($user->isMahasiswa() && ! $this->option('promote-student')) {
                $this->error('Akun masih mahasiswa. Tambahkan --promote-student hanya jika akun ini akan beralih menjadi dosen/admin.');

                return self::FAILURE;
            }
            if (! $user->isDosen() && ! $user->isMahasiswa()) {
                $this->error('Role akun tidak dikenali. Tidak ada akun yang diubah.');

                return self::FAILURE;
            }
            if ($user->isAdmin() && ! $user->needsLecturerActivation()) {
                $this->info('Akun '.$user->email.' sudah menjadi admin aktif. Password tetap sama.');

                return self::SUCCESS;
            }

            $previousRole = $user->role;
            $user->forceFill([
                'role' => User::ROLE_DOSEN,
                'is_admin' => true,
                'lecturer_activated_at' => $user->lecturer_activated_at ?? now(),
            ])->save();
            Activity::log('admin_setup', 'Menetapkan akun #'.$user->id.' dari role '.$previousRole.' sebagai dosen/admin melalui CLI.');
            $this->info('Akun '.$user->email.' kini menjadi dosen/admin aktif. Password dan data lama tetap tersimpan.');

            return self::SUCCESS;
        });
    }
}
