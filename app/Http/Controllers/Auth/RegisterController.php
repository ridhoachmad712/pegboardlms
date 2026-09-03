<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        // Di mode demo, pendaftaran mandiri tidak berlaku (akses 1-klik).
        abort_if(config('demo.enabled'), 404);

        return view('auth.register');
    }

    /**
     * Pendaftaran mandiri mahasiswa. Akun dibuat tanpa kode kelas; mahasiswa
     * bergabung ke kelas setelah masuk melalui menu Gabung Kelas. Email/NIM
     * yang sudah terdaftar diarahkan untuk masuk (mencegah akun ganda).
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(config('demo.enabled'), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'nim_nip' => ['required', 'string', 'max:50', 'unique:users,nim_nip'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'nim_nip.unique' => 'NIM ini sudah terdaftar. Jika ini milik Anda, silakan masuk.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $email = strtolower(trim($data['email']));

        // Email sudah punya akun → arahkan masuk, jangan buat akun ganda.
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar. Silakan masuk.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'nim_nip' => $data['nim_nip'],
            'role' => User::ROLE_MAHASISWA,
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Pendaftaran berhasil. Gabung ke kelas dengan kode dari dosen Anda melalui menu Gabung Kelas.');
    }
}
