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
        $filter = in_array($request->query('filter'), ['all', 'unread'], true) ? $request->query('filter') : 'all';
        $type = in_array($request->query('type'), ['grade', 'announcement', 'forum', 'revision', 'reminder'], true) ? $request->query('type') : null;
        $notifications = $request->user()->notifications()
            ->when($filter === 'unread', fn ($query) => $query->unread())
            ->when($type, fn ($query) => $query->where('type', $type))
            ->paginate(20)->withQueryString();
        $groups = $notifications->getCollection()->groupBy(function (Notification $notification) {
            if ($notification->created_at->isToday()) return 'Hari ini';
            if ($notification->created_at->isYesterday()) return 'Kemarin';
            return $notification->created_at->translatedFormat('d F Y');
        });

        return view('notifications.index', compact('notifications', 'groups', 'filter', 'type'));
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

    public function updatePreferences(Request $request): RedirectResponse
    {
        $types = ['grade', 'announcement', 'forum', 'revision', 'reminder'];
        $preferences = collect($types)->mapWithKeys(fn ($type) => [$type => $request->boolean($type)])->all();
        $request->user()->update(['notification_preferences' => $preferences]);

        return back()->with('status', 'Preferensi notifikasi disimpan.');
    }
}
