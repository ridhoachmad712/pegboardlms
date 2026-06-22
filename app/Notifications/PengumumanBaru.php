<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengumumanBaru extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->announcement;

        return (new MailMessage())
            ->subject('Pengumuman: '.$a->title)
            ->greeting('Halo '.$notifiable->name)
            ->line('Ada pengumuman baru di kelas **'.$a->course->name.'**:')
            ->line('**'.$a->title.'**')
            ->line($a->content)
            ->action('Lihat Pengumuman', route('announcements.index', $a->course));
    }
}
