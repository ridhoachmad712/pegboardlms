<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Activity;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'lms:make-admin {email : Email akun dosen pemilik instalasi}';

    protected $description = 'Tetapkan akun dosen yang disebutkan secara eksplisit sebagai admin aktif';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $user = User::where('email', $email)->where('role', User::ROLE_DOSEN)->first();
        if (! $user) {
            $this->error('Akun dosen tidak ditemukan. Perintah ini tidak membuat akun atau mengubah mahasiswa menjadi admin.');

            return self::FAILURE;
        }

        $user->forceFill([
            'is_admin' => true,
            'lecturer_activated_at' => $user->lecturer_activated_at ?? now(),
        ])->save();
        Activity::log('admin_setup', 'Menetapkan dosen #'.$user->id.' sebagai admin melalui CLI.');
        $this->info('Akun '.$user->email.' kini memiliki akses admin dan tetap dapat mengajar.');

        return self::SUCCESS;
    }
}
