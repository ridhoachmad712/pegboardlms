<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LecturerActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['pending', 'active', 'all'], true)
            ? $request->query('status') : 'pending';
        $base = User::where('role', User::ROLE_DOSEN)->where('is_admin', false);
        $pendingCount = (clone $base)->whereNull('lecturer_activated_at')->count();
        $activeCount = (clone $base)->whereNotNull('lecturer_activated_at')->count();
        $lecturers = $base->when($status === 'pending', fn ($query) => $query->whereNull('lecturer_activated_at'))
            ->when($status === 'active', fn ($query) => $query->whereNotNull('lecturer_activated_at'))
            ->when($q !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')
                ->orWhere('institution', 'like', '%'.$q.'%')))
            ->orderBy('created_at', $status === 'pending' ? 'asc' : 'desc')
            ->orderBy('id', $status === 'pending' ? 'asc' : 'desc')->paginate(20)->withQueryString();

        return view('admin.lecturers.index', compact('q', 'status', 'pendingCount', 'activeCount', 'lecturers'));
    }

    public function show(User $lecturer): Response
    {
        abort_unless($lecturer->isDosen(), 404);
        $codes = $lecturer->activationCodes()->with('creator')->latest('id')->paginate(10);

        return response()->view('admin.lecturers.show', compact('lecturer', 'codes'))->header('Cache-Control', 'private, no-store');
    }

    public function issueCode(Request $request, User $lecturer, LecturerActivation $activation): RedirectResponse
    {
        $request->validate(['payment_confirmed' => ['accepted']], [
            'payment_confirmed.accepted' => 'Konfirmasi pembayaran sebelum menerbitkan kode aktivasi.',
        ]);
        $issued = $activation->issue($lecturer, $request->user());

        return redirect()->route('admin.lecturers.show', $lecturer)->with('issued_activation', [
            'lecturer_id' => $lecturer->id,
            'code' => $issued['plain'],
            'expires_at' => $issued['code']->expires_at->translatedFormat('d M Y, H:i'),
        ]);
    }
}
