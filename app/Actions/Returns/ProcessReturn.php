<?php

namespace App\Actions\Returns;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LoanOverdue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessReturn
{
    /**
     * Konfirmasi pengembalian: kembalikan stok & hitung denda otomatis.
     */
    public function handle(Loan $loan, User $actor, ?string $tanggalKembali = null, ?string $kondisi = null, ?string $catatan = null): Loan
    {
        if (! in_array($loan->status, LoanStatus::active(), true)) {
            throw ValidationException::withMessages(['return' => 'Hanya peminjaman aktif yang dapat dikembalikan.']);
        }

        $tanggal = $tanggalKembali ? Carbon::parse($tanggalKembali) : now();
        $tarif = (int) Setting::get('tarif_denda', 1000);

        return DB::transaction(function () use ($loan, $actor, $tanggal, $kondisi, $catatan, $tarif): Loan {
            $loan->load('details');

            // Kembalikan stok.
            foreach ($loan->details as $detail) {
                Book::whereKey($detail->book_id)->lockForUpdate()->increment('stok_tersedia', $detail->jumlah);
            }

            // Catat pengembalian.
            $loan->return()->create([
                'returned_by' => $actor->id,
                'tanggal_kembali' => $tanggal->toDateString(),
                'kondisi' => $kondisi,
                'catatan' => $catatan,
            ]);

            // Hitung keterlambatan.
            $jatuhTempo = $loan->tanggal_jatuh_tempo;
            $hariTelat = $jatuhTempo && $tanggal->gt($jatuhTempo)
                ? $jatuhTempo->diffInDays($tanggal)
                : 0;

            $loan->update([
                'tanggal_kembali' => $tanggal->toDateString(),
                'status' => $hariTelat > 0 ? LoanStatus::Terlambat : LoanStatus::Dikembalikan,
            ]);

            if ($hariTelat > 0) {
                $fine = Fine::create([
                    'loan_id' => $loan->id,
                    'user_id' => $loan->user_id,
                    'jumlah_hari_telat' => $hariTelat,
                    'tarif_denda' => $tarif,
                    'total_denda' => $hariTelat * $tarif,
                    'status' => FineStatus::BelumBayar,
                ]);

                $loan->user->notify(new LoanOverdue($loan, $fine));
            }

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'loan.returned',
                'subject_type' => Loan::class,
                'subject_id' => $loan->id,
                'description' => "Pengembalian {$loan->kode_pinjam}".($hariTelat > 0 ? " (telat {$hariTelat} hari)" : ''),
                'ip_address' => request()->ip(),
            ]);

            return $loan->fresh();
        });
    }
}
