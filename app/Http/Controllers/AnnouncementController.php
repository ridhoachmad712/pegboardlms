<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Announcement;
use App\Models\Course;
use App\Notifications\PengumumanBaru;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    use ChecksCourseAccess;

    public function index(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request, $course);

        $announcements = $course->announcements()->with('author')->paginate(10)->withQueryString();

        return view('announcements.index', compact('course', 'announcements'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $announcement = $course->announcements()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        // Notifikasi in-app ke semua mahasiswa
        Notifier::toCourseStudents(
            $course,
            'announcement',
            'Pengumuman baru: '.$course->name,
            $data['title'],
            route('announcements.index', $course),
        );

        // Email ke mahasiswa yang mengaktifkan notifikasi email (bila email global aktif)
        if (\App\Models\Setting::bool('email_enabled', true)) {
            $announcement->setRelation('course', $course);
            $recipients = $course->students()->where('email_notifications', true)->get();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new PengumumanBaru($announcement));
            }
        }

        return back()->with('status', 'Pengumuman dipublikasikan.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->ensureCourseOwner($request, $announcement->course);
        $announcement->delete();

        return back()->with('status', 'Pengumuman dihapus.');
    }
}
