<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification
{
    use Queueable;

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
            'title' => 'Akun Disetujui',
            'message' => 'Selamat! Akun Anda telah disetujui. Anda kini dapat login dan meminjam buku.',
            'icon' => 'check-circle',
            'color' => 'emerald',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Perpustakaan Disetujui')
            ->greeting('Halo '.$notifiable->name)
            ->line('Akun Anda telah disetujui oleh pustakawan.')
            ->action('Login Sekarang', route('login'))
            ->line('Terima kasih telah mendaftar.');
    }
}
