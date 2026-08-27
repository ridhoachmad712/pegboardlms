<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LecturerActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ActivationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->needsLecturerActivation()) {
            return redirect()->route('dashboard');
        }

        return response()->view('auth.activate', ['user' => $request->user()])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, LecturerActivation $activation): RedirectResponse
    {
        // Accept pasted groups/spaces; the canonical code still has exactly 20 characters.
        $raw = $request->input('activation_code');
        if (is_string($raw)) {
            $request->merge(['activation_code' => strtoupper(preg_replace('/[\s-]+/', '', $raw))]);
        }
        $data = $request->validate([
            'activation_code' => ['required', 'string', 'regex:/\A[A-Z0-9]{20}\z/'],
        ], ['activation_code.regex' => 'Kode aktivasi harus terdiri dari 20 karakter huruf dan angka.']);

        $activation->redeem($request->user(), $data['activation_code']);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard.dosen')->with('status', 'Akun aktif. Akses sekali bayar Anda tidak memiliki masa kedaluwarsa.');
    }
}
