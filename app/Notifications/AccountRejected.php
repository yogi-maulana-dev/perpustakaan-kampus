<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountRejected extends Notification
{
    use Queueable;

    public function __construct(public ?string $reason = null)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pendaftaran Ditolak',
            'message' => 'Pendaftaran Anda ditolak.'.($this->reason ? ' Alasan: '.$this->reason : ' Silakan hubungi pustakawan.'),
            'icon' => 'x-circle',
            'color' => 'rose',
        ];
    }
}
