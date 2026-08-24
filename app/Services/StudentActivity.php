<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentActivity
{
    /** Gabungkan tindakan akademik mahasiswa dan pembaruan yang belum dibaca. */
    public function forStudent(User $user): array
    {
        $courses = $user->enrolledCourses()
            ->where('status', 'active')
            ->get(['courses.id', 'courses.name']);
        $courseIds = $courses->pluck('id');

        if ($courseIds->isEmpty()) {
            return ['actions' => collect(), 'updates' => collect(), 'total' => 0];
        }

        $submittedIds = $user->submissions()->pluck('assignment_id');
        $groupSubmittedIds = Assignment::whereIn('course_id', $courseIds)
            ->where('mode', Assignment::MODE_KELOMPOK)
            ->whereHas('groups', fn ($group) => $group
                ->whereHas('members', fn ($members) => $members->whereKey($user->id))
                ->has('submission'))
            ->pluck('id');

        $revisionLinks = $user->notifications()->unread()
            ->where('type', 'revision')
            ->pluck('link')
            ->filter()
            ->flip();

        $assignments = Assignment::whereIn('course_id', $courseIds)
            ->where('published', true)
            ->whereNotIn('id', $submittedIds->merge($groupSubmittedIds)->unique())
            ->with('course:id,name')
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->get()
            ->map(function (Assignment $assignment) use ($revisionLinks) {
                $url = route('assignments.show', $assignment);
                $revision = $revisionLinks->has($url);

                return [
                    'kind' => $revision ? 'revision' : ($assignment->isQuiz() ? 'quiz' : 'assignment'),
                    'title' => $assignment->title,
                    'subtitle' => $assignment->course->name,
                    'meta' => $revision ? 'Perlu dikumpulkan ulang' : ($assignment->deadline?->translatedFormat('d M Y, H:i') ?? 'Tanpa deadline'),
                    'at' => $assignment->deadline,
                    'url' => $url,
                    'urgent' => $revision || ($assignment->deadline && $assignment->deadline->isBefore(now()->addDay())),
                ];
            });

        $meetings = Meeting::whereIn('course_id', $courseIds)
            ->with(['course:id,name', 'tokens', 'attendances' => fn ($query) => $query->where('user_id', $user->id)])
            ->where(function ($query) {
                $query->whereDate('date', today())
                    ->orWhere('attend_opens_at', '<=', now())
                    ->orWhereHas('tokens', fn ($tokens) => $tokens->where('expires_at', '>', now()));
            })
            ->get()
            ->filter(fn (Meeting $meeting) => $meeting->attendanceOpen()
                && ! $meeting->attendances->contains(fn ($attendance) => $attendance->status === 'hadir'))
            ->map(fn (Meeting $meeting) => [
                'kind' => 'attendance',
                'title' => 'Absensi Pertemuan '.$meeting->number,
                'subtitle' => $meeting->course->name.' · '.$meeting->topic,
                'meta' => 'Sedang dibuka'.($meeting->attendClosesAt() ? ' hingga '.$meeting->attendClosesAt()->translatedFormat('H:i') : ''),
                'at' => $meeting->attendClosesAt(),
                'url' => route('attendance.index', $meeting->course_id),
                'urgent' => true,
            ]);

        $actions = $meetings->concat($assignments)
            ->sortBy(fn (array $item) => ($item['urgent'] ? '0' : '1').($item['at']?->format('YmdHi') ?? '999999999999'))
            ->values();

        $updates = $user->notifications()->unread()
            ->whereIn('type', ['grade', 'announcement', 'forum', 'revision'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'kind' => $notification->type,
                'title' => $notification->title,
                'subtitle' => $notification->message,
                'meta' => $notification->created_at->diffForHumans(),
                'at' => $notification->created_at,
                'url' => route('notifications.read', $notification),
            ]);

        return [
            'actions' => $actions,
            'updates' => $updates,
            'total' => $actions->count() + $updates->count(),
        ];
    }
}
