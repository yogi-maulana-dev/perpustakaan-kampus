<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoanApproved extends Notification
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
            'title' => 'Peminjaman Disetujui',
            'message' => "Peminjaman {$this->loan->kode_pinjam} disetujui. Jatuh tempo {$this->loan->tanggal_jatuh_tempo?->format('d M Y')}.",
            'icon' => 'check-circle',
            'color' => 'emerald',
        ];
    }
}
