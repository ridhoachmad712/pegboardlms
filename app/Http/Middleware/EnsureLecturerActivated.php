<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLecturerActivated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->isDosen()) {
            if ($user->isLecturerDisabled()) {
                return $this->endSession($request, 'Akun dosen Anda dinonaktifkan. Hubungi admin untuk bantuan.', 403);
            }
            $version = (int) $user->lecturer_session_version;
            $sessionVersion = $request->session()->get('lecturer_session_version');
            if ($sessionVersion === null && ($version === 0 || Auth::viaRemember())) {
                // Legacy sessions are accepted only until the first security action.
                // Remember-me tokens are rotated on every disable/reset operation.
                $request->session()->put('lecturer_session_version', $version);
            } elseif ($sessionVersion === null || (int) $sessionVersion !== $version) {
                return $this->endSession($request, 'Sesi Anda telah berakhir. Silakan login kembali.', 401);
            }
            if ($user->must_change_password && ! $request->routeIs('lecturer.password.edit', 'lecturer.password.update', 'logout')) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Ganti password sementara terlebih dahulu.', 'password_change_required' => true], 403);
                }

                return redirect()->route('lecturer.password.edit');
            }
        }

        if ($request->user()?->needsLecturerActivation()
            && ! $request->routeIs('activation.show', 'activation.store', 'lecturer.password.edit', 'lecturer.password.update', 'logout')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Akun dosen belum diaktifkan.', 'activation_required' => true], 403);
            }

            return redirect()->route('activation.show');
        }

        return $next($request);
    }

    private function endSession(Request $request, string $message, int $status): Response
    {
        // Do not rotate the current account's token when rejecting an older session.
        Auth::logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() || $request->is('api/*')
            ? response()->json(['message' => $message], $status)
            : redirect()->route('login')->withErrors(['email' => $message]);
    }
}
