<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Activity;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'lms:create-admin {email : Email admin baru} {--name= : Nama lengkap admin baru}';

    protected $description = 'Buat admin aktif baru melalui terminal, tanpa menimpa akun yang sudah ada';

    public function handle(): int
    {
        if (! Schema::hasColumn('users', 'is_admin') || ! Schema::hasColumn('users', 'lecturer_activated_at')) {
            $this->error('Jalankan php artisan migrate --force terlebih dahulu.');

            return self::FAILURE;
        }

        $data = [
            'email' => strtolower(trim((string) $this->argument('email'))),
            'name' => trim((string) $this->option('name')),
        ];
        $identity = Validator::make($data, [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        if ($identity->fails()) {
            $this->error('Isi email yang valid dan --name dengan nama admin (maksimal 255 karakter).');

            return self::FAILURE;
        }

        if (User::whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
            $this->error('Email sudah terdaftar. Tidak ada akun atau kata sandi yang diubah. Untuk akun dosen yang sudah ada, gunakan lms:make-admin.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Gunakan terminal interaktif agar kata sandi dapat dimasukkan secara tersembunyi.');

            return self::FAILURE;
        }

        // Never accept a password in CLI arguments, source code, or environment files.
        // Refuse visible-input fallback if the terminal cannot hide the password.
        $data['password'] = $this->secret('Kata sandi admin (12–72 karakter)', false);
        $data['password_confirmation'] = $this->secret('Ulangi kata sandi admin', false);
        $password = Validator::make($data, ['password' => ['required', 'string', 'min:12', 'max:72', 'confirmed']]);
        if ($password->fails() || strlen((string) $data['password']) > 72) {
            $this->error('Kata sandi harus 12–72 karakter (maksimal 72 byte) dan konfirmasinya harus sama.');

            return self::FAILURE;
        }

        try {
            $user = DB::transaction(function () use ($data) {
                $user = new User([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'], // User's hashed cast handles storage.
                    'role' => User::ROLE_DOSEN,
                ]);
                $user->forceFill(['is_admin' => true, 'lecturer_activated_at' => now()])->save();
                Activity::log('admin_setup', 'Membuat akun admin #'.$user->id.' melalui CLI.');

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            $this->error('Email sudah terdaftar. Tidak ada akun atau kata sandi yang diubah.');

            return self::FAILURE;
        }

        $this->info('Admin aktif berhasil dibuat: '.$user->email);
        $this->info('Login menggunakan kata sandi yang baru Anda masukkan.');

        return self::SUCCESS;
    }
}
