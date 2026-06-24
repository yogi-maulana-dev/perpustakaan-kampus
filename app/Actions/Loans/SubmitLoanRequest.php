<?php

namespace App\Actions\Loans;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmitLoanRequest
{
    /**
     * Mahasiswa mengajukan peminjaman sebuah buku (status pending).
     */
    public function handle(User $student, Book $book): Loan
    {
        $maxPinjam = (int) Setting::get('max_pinjam', 3);

        // Aturan bisnis: tidak boleh ada denda belum dibayar.
        if ($student->fines()->where('status', FineStatus::BelumBayar)->exists()) {
            throw ValidationException::withMessages([
                'loan' => 'Anda memiliki denda yang belum dibayar.',
            ]);
        }

        // Aturan bisnis: batas pinjaman aktif + pending.
        $aktif = $student->loans()
            ->whereIn('status', [LoanStatus::Pending->value, LoanStatus::Dipinjam->value, LoanStatus::Terlambat->value])
            ->count();

        if ($aktif >= $maxPinjam) {
            throw ValidationException::withMessages([
                'loan' => "Batas maksimal {$maxPinjam} peminjaman aktif telah tercapai.",
            ]);
        }

        if ($book->stok_tersedia < 1) {
            throw ValidationException::withMessages([
                'loan' => 'Stok buku sedang tidak tersedia.',
            ]);
        }

        // Tidak boleh mengajukan buku yang sama yang masih aktif/pending.
        $duplikat = $student->loans()
            ->whereIn('status', [LoanStatus::Pending->value, LoanStatus::Dipinjam->value, LoanStatus::Terlambat->value])
            ->whereHas('details', fn ($q) => $q->where('book_id', $book->id))
            ->exists();

        if ($duplikat) {
            throw ValidationException::withMessages([
                'loan' => 'Anda sudah mengajukan/meminjam buku ini.',
            ]);
        }

        return DB::transaction(function () use ($student, $book): Loan {
            $loan = Loan::create([
                'kode_pinjam' => 'PJM-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
                'user_id' => $student->id,
                'status' => LoanStatus::Pending,
            ]);

            $loan->details()->create([
                'book_id' => $book->id,
                'jumlah' => 1,
            ]);

            ActivityLog::create([
                'user_id' => $student->id,
                'action' => 'loan.requested',
                'subject_type' => Loan::class,
                'subject_id' => $loan->id,
                'description' => "Mengajukan peminjaman: {$book->judul}",
                'ip_address' => request()->ip(),
            ]);

            return $loan;
        });
    }
}
