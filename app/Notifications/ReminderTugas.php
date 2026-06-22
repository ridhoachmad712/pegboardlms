<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderTugas extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Assignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->assignment;

        return (new MailMessage())
            ->subject('Pengingat Deadline: '.$a->title)
            ->greeting('Halo '.$notifiable->name)
            ->line('Tugas berikut akan berakhir besok dan belum Anda kumpulkan:')
            ->line('**'.$a->title.'** — '.$a->course->name)
            ->line('Deadline: '.$a->deadline?->translatedFormat('d M Y H:i').' WITA')
            ->action('Buka Tugas', route('assignments.show', $a))
            ->line('Segera kumpulkan sebelum deadline. Terima kasih.');
    }
}
