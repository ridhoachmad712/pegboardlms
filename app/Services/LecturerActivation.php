<?php

namespace App\Services;

use App\Models\LecturerActivationCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LecturerActivation
{
    public const CODE_LENGTH = 20;

    /** Returns the plaintext once; only its SHA-256 hash is persisted in the code table. */
    public function issue(User $lecturer, User $admin): array
    {
        abort_unless($admin->isAdmin() && ! $admin->needsLecturerActivation() && ! $admin->isLecturerDisabled(), 403);

        return DB::transaction(function () use ($lecturer, $admin) {
            // Lock the account first in both issuance and redemption to serialize them.
            $account = User::whereKey($lecturer->id)->lockForUpdate()->firstOrFail();
            abort_unless($account->isDosen(), 404);
            if ($account->isLecturerDisabled()) {
                throw ValidationException::withMessages(['activation' => 'Aktifkan kembali akses akun sebelum menerbitkan kode.']);
            }
            if (! $account->needsLecturerActivation()) {
                throw ValidationException::withMessages(['activation' => 'Akun dosen ini sudah aktif.']);
            }

            $account->activationCodes()->whereNull('used_at')->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $plain = $this->generateCode();
            $code = $account->activationCodes()->create([
                'created_by' => $admin->id,
                'code_hash' => hash('sha256', $plain),
                'expires_at' => now()->addDays(config('licensing.code_valid_days')),
            ]);
            Activity::log('activation_code', 'Menerbitkan kode aktivasi untuk dosen #'.$account->id.'. Pembayaran diverifikasi admin.');

            return ['plain' => $plain, 'code' => $code];
        });
    }

    public function redeem(User $lecturer, string $plain): void
    {
        DB::transaction(function () use ($lecturer, $plain) {
            $account = User::whereKey($lecturer->id)->lockForUpdate()->firstOrFail();
            abort_unless($account->isDosen(), 403);

            $code = LecturerActivationCode::where('user_id', $account->id)
                ->where('code_hash', hash('sha256', $plain))->lockForUpdate()->first();

            if ($account->isLecturerDisabled() || $account->must_change_password || ! $account->needsLecturerActivation() || ! $code || ! $code->isUsable()) {
                throw ValidationException::withMessages([
                    'activation_code' => 'Kode tidak valid atau tidak dapat digunakan. Pastikan kode diberikan untuk akun Anda; hubungi admin bila perlu kode baru.',
                ]);
            }

            $code->update(['used_at' => now()]);
            $account->forceFill(['lecturer_activated_at' => now()])->save();
            Activity::log('lecturer_activation', 'Dosen #'.$account->id.' mengaktifkan akses sekali bayar.');
        });
    }

    private function generateCode(): string
    {
        // Avoid ambiguous I/O/0/1; always include both letters and digits.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (! preg_match('/[A-Z]/', $code) || ! preg_match('/[0-9]/', $code)
            || LecturerActivationCode::where('code_hash', hash('sha256', $code))->exists());

        return $code;
    }
}
