<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Course;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForumController extends Controller
{
    use ChecksCourseAccess;

    public function index(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request, $course);

        $threads = $course->forumThreads()
            ->with('author')
            ->withCount('replies')
            ->orderByDesc('pinned')
            ->latest()
            ->get();

        return view('forum.index', compact('course', 'threads'));
    }

    public function storeThread(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseAccess($request, $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $thread = $course->forumThreads()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('forum.show', $thread)
            ->with('status', 'Diskusi dibuat.');
    }

    public function show(Request $request, ForumThread $thread): View
    {
        $thread->load('course', 'author', 'replies.author');
        $this->ensureCourseAccess($request, $thread->course);

        return view('forum.show', compact('thread'));
    }

    public function storeReply(Request $request, ForumThread $thread): RedirectResponse
    {
        $thread->load('course');
        $this->ensureCourseAccess($request, $thread->course);

        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $thread->replies()->create([
            'content' => $data['content'],
            'user_id' => $request->user()->id,
        ]);

        // Notifikasi ke pembuat thread (jika bukan dia yang membalas)
        if ($thread->user_id !== $request->user()->id) {
            Notifier::toUser(
                $thread->user_id,
                'forum_reply',
                'Balasan baru di diskusi Anda',
                'Pada: '.$thread->title,
                route('forum.show', $thread),
            );
        }

        return back()->with('status', 'Balasan dikirim.');
    }

    public function pin(Request $request, ForumThread $thread): RedirectResponse
    {
        $this->ensureCourseOwner($request, $thread->course);
        $thread->update(['pinned' => ! $thread->pinned]);

        return back()->with('status', $thread->pinned ? 'Diskusi disematkan.' : 'Sematan dilepas.');
    }

    public function destroyThread(Request $request, ForumThread $thread): RedirectResponse
    {
        $this->authorizeAuthorOrOwner($request, $thread->course, $thread->user_id);
        $course = $thread->course;
        $thread->delete();

        return redirect()->route('forum.index', $course)->with('status', 'Diskusi dihapus.');
    }

    public function destroyReply(Request $request, ForumReply $reply): RedirectResponse
    {
        $reply->load('thread.course');
        $this->authorizeAuthorOrOwner($request, $reply->thread->course, $reply->user_id);
        $reply->delete();

        return back()->with('status', 'Balasan dihapus.');
    }

    /** Penulis konten atau dosen pemilik kelas boleh menghapus. */
    private function authorizeAuthorOrOwner(Request $request, Course $course, int $authorId): void
    {
        $user = $request->user();
        $isOwner = $user->isDosen() && $course->user_id === $user->id;
        abort_unless($authorId === $user->id || $isOwner, 403);
    }
}
