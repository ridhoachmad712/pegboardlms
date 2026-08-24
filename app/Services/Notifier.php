<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class Notifier
{
    public static function toUser(User|int $user, string $type, string $title, ?string $message = null, ?string $link = null): void
    {
        $recipient = $user instanceof User ? $user : User::find($user);
        if (! $recipient || ! $recipient->wantsNotification($type)) {
            return;
        }

        Notification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }

    /** Kirim ke semua mahasiswa dalam kelas (opsional kecualikan satu user). */
    public static function toCourseStudents(Course $course, string $type, string $title, ?string $message = null, ?string $link = null, ?int $exceptUserId = null): void
    {
        $ids = $course->students()->get(['users.id', 'users.notification_preferences'])
            ->filter(fn (User $student) => $student->wantsNotification($type))
            ->pluck('id')
            ->when($exceptUserId, fn (Collection $c) => $c->reject(fn ($id) => $id === $exceptUserId));

        $now = now();
        $rows = $ids->map(fn ($id) => [
            'user_id' => $id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            Notification::insert($rows);
        }
    }
}
