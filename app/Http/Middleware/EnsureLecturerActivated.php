<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLecturerActivated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->needsLecturerActivation()
            && ! $request->routeIs('activation.show', 'activation.store', 'logout')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Akun dosen belum diaktifkan.', 'activation_required' => true], 403);
            }

            return redirect()->route('activation.show');
        }

        return $next($request);
    }
}
