<?php

namespace App\Actions\Loans;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenewLoan
{
    /**
     * Perpanjang masa pinjam: jatuh tempo mundur otomatis +durasi (Carbon).
     */
    public function handle(Loan $loan, User $actor): Loan
    {
        if (! (bool) Setting::get('perpanjangan_aktif', true)) {
            throw ValidationException::withMessages(['renew' => 'Fitur perpanjangan sedang dinonaktifkan.']);
        }

        if ($loan->status !== LoanStatus::Dipinjam) {
            throw ValidationException::withMessages(['renew' => 'Hanya peminjaman aktif (belum terlambat) yang bisa diperpanjang.']);
        }

        if ($loan->tanggal_jatuh_tempo && $loan->tanggal_jatuh_tempo->isPast()) {
            throw ValidationException::withMessages(['renew' => 'Sudah melewati jatuh tempo, tidak dapat diperpanjang.']);
        }

        if ($loan->user->fines()->where('status', FineStatus::BelumBayar)->exists()) {
            throw ValidationException::withMessages(['renew' => 'Tidak dapat diperpanjang karena ada denda belum dibayar.']);
        }

        $max = (int) Setting::get('max_perpanjangan', 2);

        if ($loan->jumlah_perpanjangan >= $max) {
            throw ValidationException::withMessages(['renew' => "Batas maksimal perpanjangan ({$max}x) telah tercapai."]);
        }

        $durasi = (int) Setting::get('durasi_pinjam', 7);

        return DB::transaction(function () use ($loan, $actor, $durasi): Loan {
            $loan->update([
                'tanggal_jatuh_tempo' => $loan->tanggal_jatuh_tempo->copy()->addDays($durasi),
                'jumlah_perpanjangan' => $loan->jumlah_perpanjangan + 1,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'loan.renewed',
                'subject_type' => Loan::class,
                'subject_id' => $loan->id,
                'description' => "Perpanjangan {$loan->kode_pinjam} (ke-{$loan->jumlah_perpanjangan}), jatuh tempo baru {$loan->tanggal_jatuh_tempo->format('d M Y')}",
                'ip_address' => request()->ip(),
            ]);

            return $loan->fresh();
        });
    }
}
