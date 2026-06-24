<?php

namespace App\Notifications;

use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoanOverdue extends Notification
{
    use Queueable;

    public function __construct(public Loan $loan, public Fine $fine)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pengembalian Terlambat',
            'message' => "Buku dikembalikan terlambat {$this->fine->jumlah_hari_telat} hari. Denda Rp ".number_format($this->fine->total_denda, 0, ',', '.').'.',
            'icon' => 'cash',
            'color' => 'rose',
        ];
    }
}
