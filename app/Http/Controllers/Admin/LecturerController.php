<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LecturerAccount;
use App\Services\LecturerActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['pending', 'active', 'disabled', 'all'], true)
            ? $request->query('status') : 'pending';
        $base = User::where('role', User::ROLE_DOSEN)->where('is_admin', false);
        $pendingCount = (clone $base)->whereNull('lecturer_disabled_at')->whereNull('lecturer_activated_at')->count();
        $activeCount = (clone $base)->whereNull('lecturer_disabled_at')->whereNotNull('lecturer_activated_at')->count();
        $disabledCount = (clone $base)->whereNotNull('lecturer_disabled_at')->count();
        $lecturers = $base->when($status === 'pending', fn ($query) => $query->whereNull('lecturer_disabled_at')->whereNull('lecturer_activated_at'))
            ->when($status === 'active', fn ($query) => $query->whereNull('lecturer_disabled_at')->whereNotNull('lecturer_activated_at'))
            ->when($status === 'disabled', fn ($query) => $query->whereNotNull('lecturer_disabled_at'))
            ->when($q !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')
                ->orWhere('institution', 'like', '%'.$q.'%')))
            ->orderBy('created_at', $status === 'pending' ? 'asc' : 'desc')
            ->orderBy('id', $status === 'pending' ? 'asc' : 'desc')->paginate(20)->withQueryString();

        return view('admin.lecturers.index', compact('q', 'status', 'pendingCount', 'activeCount', 'disabledCount', 'lecturers'));
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

    public function disable(Request $request, User $lecturer, LecturerAccount $accounts): RedirectResponse
    {
        $accounts->setDisabled($lecturer, $request->user(), true);

        return redirect()->route('admin.lecturers.show', $lecturer)->with('status', 'Akses dosen dinonaktifkan. Kelas dan data akademik tetap tersimpan.');
    }

    public function enable(Request $request, User $lecturer, LecturerAccount $accounts): RedirectResponse
    {
        $accounts->setDisabled($lecturer, $request->user(), false);

        return redirect()->route('admin.lecturers.show', $lecturer)->with('status', 'Penonaktifan dicabut. Akun yang belum diaktivasi tetap memerlukan kode aktivasi.');
    }

    public function resetPassword(Request $request, User $lecturer, LecturerAccount $accounts): RedirectResponse
    {
        $plain = $accounts->resetPassword($lecturer, $request->user());

        return redirect()->route('admin.lecturers.show', $lecturer)->with('lecturer_password_reset', [
            'lecturer_id' => $lecturer->id,
            // Flash expires after the next request; never persist plaintext in the session store.
            'encrypted_password' => Crypt::encryptString($plain),
        ]);
    }
}
