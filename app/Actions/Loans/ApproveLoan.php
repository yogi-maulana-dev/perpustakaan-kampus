<?php

namespace App\Actions\Loans;

use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LoanApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveLoan
{
    /**
     * Setujui pengajuan peminjaman: tetapkan tanggal & kurangi stok.
     */
    public function handle(Loan $loan, User $actor): Loan
    {
        if ($loan->status !== LoanStatus::Pending) {
            throw ValidationException::withMessages(['loan' => 'Peminjaman ini sudah diproses.']);
        }

        $durasi = (int) Setting::get('durasi_pinjam', 7);

        return DB::transaction(function () use ($loan, $actor, $durasi): Loan {
            $loan->load('details.book');

            // Kunci baris buku & validasi stok.
            foreach ($loan->details as $detail) {
                $book = Book::whereKey($detail->book_id)->lockForUpdate()->first();

                if (! $book || $book->stok_tersedia < $detail->jumlah) {
                    throw ValidationException::withMessages([
                        'loan' => "Stok buku \"{$book?->judul}\" tidak mencukupi.",
                    ]);
                }

                $book->decrement('stok_tersedia', $detail->jumlah);
            }

            $loan->update([
                'status' => LoanStatus::Dipinjam,
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays($durasi)->toDateString(),
                'approved_by' => $actor->id,
            ]);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'loan.approved',
                'subject_type' => Loan::class,
                'subject_id' => $loan->id,
                'description' => "Menyetujui peminjaman {$loan->kode_pinjam}",
                'ip_address' => request()->ip(),
            ]);

            $loan->user->notify(new LoanApproved($loan));

            return $loan;
        });
    }
}
