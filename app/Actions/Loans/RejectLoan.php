<?php

namespace App\Actions\Loans;

use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectLoan
{
    /**
     * Tolak pengajuan peminjaman (stok tidak berubah).
     */
    public function handle(Loan $loan, User $actor, ?string $reason = null): Loan
    {
        if ($loan->status !== LoanStatus::Pending) {
            throw ValidationException::withMessages(['loan' => 'Peminjaman ini sudah diproses.']);
        }

        return DB::transaction(function () use ($loan, $actor, $reason): Loan {
            $loan->update([
                'status' => LoanStatus::Ditolak,
                'approved_by' => $actor->id,
                'catatan' => $reason,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'loan.rejected',
                'subject_type' => Loan::class,
                'subject_id' => $loan->id,
                'description' => "Menolak peminjaman {$loan->kode_pinjam}".($reason ? " ($reason)" : ''),
                'ip_address' => request()->ip(),
            ]);

            $loan->user->notify(new LoanRejected($loan, $reason));

            return $loan;
        });
    }
}
