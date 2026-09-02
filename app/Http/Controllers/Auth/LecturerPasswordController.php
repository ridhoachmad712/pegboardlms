<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LecturerPasswordController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return response()->view('auth.lecturer-password')->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed', function ($attribute, $value, $fail) {
                if (strlen($value) > 72) {
                    $fail('Password maksimal 72 byte.');
                }
            }],
        ], [
            'password.min' => 'Gunakan password baru minimal 12 karakter.',
            'password.max' => 'Password maksimal 72 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $version = DB::transaction(function () use ($request, $data): int {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->isDosen() && ! $user->isLecturerDisabled(), 403);
            if (! $user->must_change_password
                || (int) $request->session()->get('lecturer_session_version', -1) !== $user->lecturer_session_version
                || ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Password sementara tidak sesuai atau sudah diganti. Silakan login kembali.']);
            }
            if (Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages(['password' => 'Password baru harus berbeda dari password sementara.']);
            }
            $user->forceFill([
                'password' => $data['password'],
                'must_change_password' => false,
                'lecturer_session_version' => $user->lecturer_session_version + 1,
                'remember_token' => Str::random(60),
            ])->save();
            Activity::log('lecturer_password_changed', 'Dosen #'.$user->id.' mengganti password sementara.');

            return $user->lecturer_session_version;
        });

        $request->session()->regenerate();
        $request->session()->put('lecturer_session_version', $version);
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard')->with('status', 'Password baru tersimpan. Gunakan password ini untuk login berikutnya.');
    }
}
