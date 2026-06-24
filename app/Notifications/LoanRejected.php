<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoanRejected extends Notification
{
    use Queueable;

    public function __construct(public Loan $loan, public ?string $reason = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Peminjaman Ditolak',
            'message' => "Peminjaman {$this->loan->kode_pinjam} ditolak.".($this->reason ? " Alasan: {$this->reason}" : ''),
            'icon' => 'x-circle',
            'color' => 'rose',
        ];
    }
}
