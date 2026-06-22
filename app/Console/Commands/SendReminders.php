<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Notifications\ReminderTugas;
use App\Services\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendReminders extends Command
{
    protected $signature = 'lms:send-reminders';

    protected $description = 'Kirim pengingat H-1 (lonceng in-app + email) untuk tugas/kuis yang belum dikumpulkan';

    public function handle(): int
    {
        $emailEnabled = \App\Models\Setting::bool('email_enabled', true);

        // Tugas dengan deadline besok (rentang hari)
        $start = now()->addDay()->startOfDay();
        $end = now()->addDay()->endOfDay();

        $assignments = Assignment::where('published', true)
            ->whereBetween('deadline', [$start, $end])
            ->with('course.students', 'submissions')
            ->get();

        $inApp = 0;
        $email = 0;

        foreach ($assignments as $assignment) {
            $submittedIds = $assignment->submissions->pluck('user_id')->all();

            $pending = $assignment->course->students->whereNotIn('id', $submittedIds);

            if ($pending->isEmpty()) {
                continue;
            }

            // Notifikasi in-app (lonceng) untuk semua yang belum mengumpulkan
            foreach ($pending as $student) {
                Notifier::toUser(
                    $student->id,
                    'reminder',
                    'Pengingat: tugas berakhir besok',
                    $assignment->title.' — '.$assignment->course->name.'. Segera kumpulkan sebelum deadline.',
                    route('assignments.show', $assignment),
                );
            }
            $inApp += $pending->count();

            // Email hanya bila diaktifkan & mahasiswa tidak menonaktifkan email
            if ($emailEnabled) {
                $recipients = $pending->where('email_notifications', true);
                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, new ReminderTugas($assignment));
                    $email += $recipients->count();
                }
            }
        }

        $this->info("Reminder {$assignments->count()} tugas: {$inApp} notifikasi in-app, {$email} email.");

        return self::SUCCESS;
    }
}
