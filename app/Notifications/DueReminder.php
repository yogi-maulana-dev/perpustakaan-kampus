<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DueReminder extends Notification
{
    use Queueable;

    public function __construct(public Loan $loan)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengingat Jatuh Tempo',
            'message' => "Buku pada peminjaman {$this->loan->kode_pinjam} jatuh tempo besok ({$this->loan->tanggal_jatuh_tempo?->format('d M Y')}). Segera kembalikan untuk menghindari denda.",
            'icon' => 'clock',
            'color' => 'amber',
        ];
    }
}
