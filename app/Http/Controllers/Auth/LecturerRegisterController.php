<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LecturerRegisterController extends Controller
{
    public function create(): View
    {
        abort_if(config('demo.enabled'), 404);

        return view('auth.register-lecturer');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(config('demo.enabled'), 404);

        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'institution' => ['required', 'string', 'max:255'],
            'nim_nip' => ['nullable', 'string', 'max:50', 'unique:users,nim_nip'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
        ], [
            'email.unique' => 'Email sudah terdaftar. Silakan masuk dengan akun Anda.',
            'nim_nip.unique' => 'Identitas dosen ini sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = DB::transaction(function () use ($data) {
            // Never accept role, admin permissions, or activation from the registration payload.
            $user = User::create(array_merge($data, ['role' => User::ROLE_DOSEN]));
            $now = now();
            $notifications = User::where('role', User::ROLE_DOSEN)->where('is_admin', true)
                ->pluck('id')->map(fn ($id) => [
                    'user_id' => $id,
                    'type' => 'lecturer_registration',
                    'title' => 'Dosen baru menunggu aktivasi',
                    'message' => $user->name.' — '.$user->institution,
                    'link' => route('admin.lecturers.show', $user, false),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();
            if ($notifications) {
                Notification::insert($notifications);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('activation.show')->with('status', 'Pendaftaran berhasil. Akun Anda menunggu aktivasi admin.');
    }
}
