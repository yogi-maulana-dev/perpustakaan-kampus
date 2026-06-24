<?php

namespace Tests\Feature;

use App\Actions\Loans\ApproveLoan;
use App\Actions\Loans\SubmitLoanRequest;
use App\Actions\Returns\ProcessReturn;
use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function student(): User
    {
        return User::where('email', 'budi@student.test')->first();
    }

    private function librarian(): User
    {
        return User::where('email', 'librarian@perpustakaan.test')->first();
    }

    public function test_full_loan_lifecycle_with_late_fine(): void
    {
        $student = $this->student();
        $librarian = $this->librarian();
        $book = Book::where('stok_tersedia', '>', 0)->first();
        $stokAwal = $book->stok_tersedia;

        // 1. Ajukan pinjam
        $loan = app(SubmitLoanRequest::class)->handle($student, $book);
        $this->assertEquals(LoanStatus::Pending, $loan->status);

        // 2. Approve -> stok berkurang, tanggal terisi
        app(ApproveLoan::class)->handle($loan, $librarian);
        $loan->refresh();
        $book->refresh();
        $this->assertEquals(LoanStatus::Dipinjam, $loan->status);
        $this->assertEquals($stokAwal - 1, $book->stok_tersedia);
        $this->assertNotNull($loan->tanggal_jatuh_tempo);

        // 3. Kembalikan 3 hari setelah jatuh tempo -> denda
        $tarif = (int) Setting::get('tarif_denda');
        $telat = $loan->tanggal_jatuh_tempo->copy()->addDays(3);
        app(ProcessReturn::class)->handle($loan, $librarian, $telat->toDateString(), 'Baik');

        $loan->refresh();
        $book->refresh();
        $this->assertEquals(LoanStatus::Terlambat, $loan->status);
        $this->assertEquals($stokAwal, $book->stok_tersedia); // stok kembali
        $this->assertNotNull($loan->fine);
        $this->assertEquals(3, $loan->fine->jumlah_hari_telat);
        $this->assertEquals(3 * $tarif, $loan->fine->total_denda);
        $this->assertEquals(FineStatus::BelumBayar, $loan->fine->status);
    }

    public function test_on_time_return_has_no_fine(): void
    {
        $student = $this->student();
        $librarian = $this->librarian();
        $book = Book::where('stok_tersedia', '>', 0)->first();

        $loan = app(SubmitLoanRequest::class)->handle($student, $book);
        app(ApproveLoan::class)->handle($loan, $librarian);
        $loan->refresh();

        // Kembalikan tepat di tanggal jatuh tempo
        app(ProcessReturn::class)->handle($loan, $librarian, $loan->tanggal_jatuh_tempo->toDateString());

        $loan->refresh();
        $this->assertEquals(LoanStatus::Dikembalikan, $loan->status);
        $this->assertNull($loan->fine);
    }

    public function test_cannot_borrow_when_stock_empty(): void
    {
        $student = $this->student();
        $book = Book::where('stok_tersedia', '>', 0)->first();
        $book->update(['stok_tersedia' => 0]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(SubmitLoanRequest::class)->handle($student, $book);
    }
}
