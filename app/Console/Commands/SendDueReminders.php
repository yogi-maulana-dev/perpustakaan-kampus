<?php

namespace App\Console\Commands;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Setting;
use App\Notifications\DueReminder;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'loans:remind';

    protected $description = 'Pengingat H-1, tandai terlambat, & perbarui denda berjalan untuk yang lewat tempo.';

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

        // 3. Denda BERJALAN: buat/perbarui denda untuk yang belum dikembalikan & lewat tempo.
        $tarif = (int) Setting::get('tarif_denda', 1000);
        $aktif = Loan::with('fine')
            ->whereIn('status', [LoanStatus::Dipinjam, LoanStatus::Terlambat])
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString())
            ->get();

        $berjalan = 0;
        foreach ($aktif as $loan) {
            $hari = $loan->daysLate();
            if ($hari < 1) {
                continue;
            }

            // Jangan ubah denda yang sudah Lunas / Dibebaskan.
            if ($loan->fine && $loan->fine->status !== FineStatus::BelumBayar) {
                continue;
            }

            Fine::updateOrCreate(
                ['loan_id' => $loan->id],
                [
                    'user_id' => $loan->user_id,
                    'jumlah_hari_telat' => $hari,
                    'tarif_denda' => $tarif,
                    'total_denda' => $hari * $tarif,
                    'status' => FineStatus::BelumBayar,
                ],
            );
            $berjalan++;
        }

        $this->info("Reminder: {$reminders->count()} · Ditandai terlambat: {$overdue} · Denda berjalan diperbarui: {$berjalan}.");

        return self::SUCCESS;
    }
}
