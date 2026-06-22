<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Akses 1-klik mode demo: login otomatis sebagai dosen/mahasiswa tanpa kata sandi. */
    public function demo(Request $request, string $role): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $email = $role === User::ROLE_DOSEN
            ? config('demo.dosen_email')
            : config('demo.mahasiswa_email');

        // Pastikan akun demo ada (seeder bisa memperkaya datanya kemudian).
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $role === User::ROLE_DOSEN ? 'Dosen Demo' : 'Mahasiswa Demo',
                'password' => Hash::make(Str::random(40)),
                'role' => $role,
                'nim_nip' => $role === User::ROLE_DOSEN ? '0000000000' : '2000000000',
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
