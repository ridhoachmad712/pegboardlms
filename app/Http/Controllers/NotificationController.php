<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /** Jumlah notifikasi belum dibaca (untuk auto-refresh lonceng via polling). */
    public function count(Request $request): JsonResponse
    {
        return response()->json(['unread' => $request->user()->notifications()->unread()->count()]);
    }

    /** Tandai satu notifikasi dibaca lalu arahkan ke tautannya. */
    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->isUnread()) {
            $notification->update(['read_at' => now()]);
        }

        return redirect($notification->link ?: route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->unread()->update(['read_at' => now()]);

        return back()->with('status', 'Semua notifikasi ditandai dibaca.');
    }
}
