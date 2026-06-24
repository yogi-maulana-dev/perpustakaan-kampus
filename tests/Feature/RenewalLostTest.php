<?php

namespace Tests\Feature;

use App\Actions\Loans\ApproveLoan;
use App\Actions\Loans\MarkLoanLost;
use App\Actions\Loans\RenewLoan;
use App\Actions\Loans\SubmitLoanRequest;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RenewalLostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function activeLoan(): Loan
    {
        $student = User::where('email', 'budi@student.test')->first();
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $book = Book::where('stok_tersedia', '>', 0)->first();

        $loan = app(SubmitLoanRequest::class)->handle($student, $book);
        app(ApproveLoan::class)->handle($loan, $librarian);

        return $loan->fresh();
    }

    public function test_student_can_renew_until_max(): void
    {
        $loan = $this->activeLoan();
        $student = $loan->user;
        $durasi = (int) Setting::get('durasi_pinjam');
        $jatuhTempoAwal = $loan->tanggal_jatuh_tempo->copy();

        app(RenewLoan::class)->handle($loan, $student);
        $loan->refresh();
        $this->assertEquals(1, $loan->jumlah_perpanjangan);
        $this->assertEquals($jatuhTempoAwal->copy()->addDays($durasi)->toDateString(), $loan->tanggal_jatuh_tempo->toDateString());

        app(RenewLoan::class)->handle($loan, $student);
        $loan->refresh();
        $this->assertEquals(2, $loan->jumlah_perpanjangan);

        // Perpanjangan ke-3 ditolak (max 2).
        $this->expectException(ValidationException::class);
        app(RenewLoan::class)->handle($loan, $student);
    }

    public function test_cannot_renew_when_disabled(): void
    {
        Setting::set('perpanjangan_aktif', 0);
        $loan = $this->activeLoan();

        $this->expectException(ValidationException::class);
        app(RenewLoan::class)->handle($loan, $loan->user);
    }

    public function test_cannot_renew_when_overdue(): void
    {
        $loan = $this->activeLoan();
        $loan->update(['tanggal_jatuh_tempo' => now()->subDay()]);

        $this->expectException(ValidationException::class);
        app(RenewLoan::class)->handle($loan->fresh(), $loan->user);
    }

    public function test_mark_loan_lost_reduces_stock_and_sets_status(): void
    {
        $loan = $this->activeLoan();
        $librarian = User::where('email', 'librarian@perpustakaan.test')->first();
        $book = $loan->details->first()->book;
        $stokTotalAwal = $book->jumlah_stok;

        app(MarkLoanLost::class)->handle($loan, $librarian, 'Tidak dikembalikan');

        $loan->refresh();
        $book->refresh();
        $this->assertEquals(LoanStatus::Hilang, $loan->status);
        $this->assertEquals($stokTotalAwal - 1, $book->jumlah_stok);
    }
}
