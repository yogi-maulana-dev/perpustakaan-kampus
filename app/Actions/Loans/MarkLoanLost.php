<?php

namespace App\Actions\Loans;

use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkLoanLost
{
    /**
     * Tandai peminjaman sebagai HILANG: stok total buku berkurang permanen.
     */
    public function handle(Loan $loan, User $actor, ?string $catatan = null): Loan
    {
        if (! in_array($loan->status, LoanStatus::active(), true)) {
            throw ValidationException::withMessages(['lost' => 'Hanya peminjaman aktif yang dapat ditandai hilang.']);
        }

        return DB::transaction(function () use ($loan, $actor, $catatan): Loan {
            $loan->load('details');

            // Eksemplar hilang permanen → kurangi total stok (stok_tersedia sudah berkurang saat dipinjam).
            foreach ($loan->details as $detail) {
                $book = Book::whereKey($detail->book_id)->lockForUpdate()->first();
                if ($book) {
                    $book->decrement('jumlah_stok', min($detail->jumlah, $book->jumlah_stok));
                }
            }

            $loan->update([
                'status' => LoanStatus::Hilang,
                'catatan' => $catatan,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'loan.lost',
                'subject_type' => Loan::class,
                'subject_id' => $loan->id,
                'description' => "Menandai peminjaman {$loan->kode_pinjam} sebagai HILANG".($catatan ? " ($catatan)" : ''),
                'ip_address' => request()->ip(),
            ]);

            return $loan->fresh();
        });
    }
}
