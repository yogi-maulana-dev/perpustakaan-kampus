<?php

namespace Tests\Feature;

use App\Actions\Loans\ApproveLoan;
use App\Actions\Loans\SubmitLoanRequest;
use App\Actions\Returns\ProcessReturn;
use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Fine;
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

    public function test_late_return_now_produces_whole_day_fine(): void
    {
        $student = $this->student();
        $librarian = $this->librarian();
        $book = Book::where('stok_tersedia', '>', 0)->first();

        $loan = app(SubmitLoanRequest::class)->handle($student, $book);
        app(ApproveLoan::class)->handle($loan, $librarian);
        $loan->refresh();

        // Jatuh tempo 5 hari lalu → pengembalian "sekarang" (dengan jam) harus telat 5 hari BULAT.
        $loan->update(['tanggal_jatuh_tempo' => now()->subDays(5)->toDateString()]);
        $loan->refresh();

        $tarif = (int) Setting::get('tarif_denda');
        app(ProcessReturn::class)->handle($loan, $librarian); // tanpa tanggal → now() (ada jam)

        $loan->refresh();
        $this->assertNotNull($loan->fine);
        $this->assertEquals(5, $loan->fine->jumlah_hari_telat);       // bukan 5.2xx
        $this->assertEquals(5 * $tarif, $loan->fine->total_denda);    // bukan pecahan
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

    public function test_overdue_loan_accrues_running_fine_via_scheduler(): void
    {
        $student = $this->student();
        $lib = $this->librarian();
        $book = Book::where('stok_tersedia', '>', 0)->first();

        $loan = app(SubmitLoanRequest::class)->handle($student, $book);
        app(ApproveLoan::class)->handle($loan, $lib);
        $loan->refresh();
        $loan->update(['tanggal_jatuh_tempo' => now()->subDays(3)->toDateString()]);

        $this->assertNull($loan->fresh()->fine); // belum ada denda sebelum scheduler

        $this->artisan('loans:remind')->assertSuccessful();

        $loan->refresh();
        $tarif = (int) Setting::get('tarif_denda');
        $this->assertEquals(LoanStatus::Terlambat, $loan->status);
        $this->assertNotNull($loan->fine);
        $this->assertEquals(3, $loan->fine->jumlah_hari_telat);
        $this->assertEquals(3 * $tarif, $loan->fine->total_denda);
        $this->assertEquals(FineStatus::BelumBayar, $loan->fine->status);
    }

    public function test_return_updates_running_fine_without_duplicate(): void
    {
        $student = $this->student();
        $lib = $this->librarian();
        $book = Book::where('stok_tersedia', '>', 0)->first();

        $loan = app(SubmitLoanRequest::class)->handle($student, $book);
        app(ApproveLoan::class)->handle($loan, $lib);
        $loan->refresh();
        $loan->update(['tanggal_jatuh_tempo' => now()->subDays(5)->toDateString()]);

        $this->artisan('loans:remind'); // denda berjalan tercatat (5 hari)
        app(ProcessReturn::class)->handle($loan->fresh(), $lib); // dikembalikan sekarang

        $this->assertEquals(1, Fine::where('loan_id', $loan->id)->count()); // tidak dobel
        $this->assertEquals(5, $loan->fresh()->fine->jumlah_hari_telat);
    }

    public function test_scheduler_does_not_overwrite_paid_fine(): void
    {
        $student = $this->student();
        $lib = $this->librarian();
        $book = Book::where('stok_tersedia', '>', 0)->first();

        $loan = app(SubmitLoanRequest::class)->handle($student, $book);
        app(ApproveLoan::class)->handle($loan, $lib);
        $loan->refresh();
        $loan->update(['tanggal_jatuh_tempo' => now()->subDays(2)->toDateString()]);

        $this->artisan('loans:remind');
        $fine = $loan->fresh()->fine;
        $fine->update(['status' => FineStatus::Lunas]);

        $this->artisan('loans:remind'); // jalan lagi → tak boleh mengubah yang lunas
        $this->assertEquals(FineStatus::Lunas, $fine->fresh()->status);
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
