<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Notifications\DueReminder;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'loans:remind';

    protected $description = 'Kirim pengingat H-1 jatuh tempo & tandai peminjaman yang lewat tempo sebagai terlambat.';

    public function handle(): int
    {
        // 1. Pengingat H-1 untuk peminjaman yang masih dipinjam.
        $besok = now()->addDay()->toDateString();
        $reminders = Loan::with('user')
            ->where('status', LoanStatus::Dipinjam)
            ->whereDate('tanggal_jatuh_tempo', $besok)
            ->get();

        foreach ($reminders as $loan) {
            $loan->user?->notify(new DueReminder($loan));
        }

        // 2. Tandai peminjaman yang sudah lewat jatuh tempo sebagai terlambat.
        $overdue = Loan::where('status', LoanStatus::Dipinjam)
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString())
            ->update(['status' => LoanStatus::Terlambat]);

        $this->info("Reminder terkirim: {$reminders->count()}. Ditandai terlambat: {$overdue}.");

        return self::SUCCESS;
    }
}
