<?php

namespace App\Http\Controllers;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Exports\BooksExport;
use App\Exports\TransactionsExport;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /** Tipe laporan PDF yang valid. */
    private const PDF_TYPES = ['books', 'students', 'loans', 'fines', 'renewals'];

    /** Tipe laporan Excel yang valid. */
    private const EXCEL_TYPES = ['books', 'transactions', 'renewals'];

    public function pdf(Request $request, string $type): Response
    {
        abort_unless(in_array($type, self::PDF_TYPES, true), 404);

        [$from, $to] = $this->range($request);
        $data = $this->buildData($type, $from, $to);

        $pdf = Pdf::loadView("reports.{$type}", $data)->setPaper('a4', 'landscape');

        return $pdf->download("laporan-{$type}-".now()->format('Ymd-His').'.pdf');
    }

    public function excel(Request $request, string $type): Response
    {
        abort_unless(in_array($type, self::EXCEL_TYPES, true), 404);

        [$from, $to] = $this->range($request);

        $export = match ($type) {
            'books' => new BooksExport(),
            'transactions' => new TransactionsExport($from, $to),
            'renewals' => new \App\Exports\CardRenewalsExport($from, $to),
        };

        return Excel::download($export, "laporan-{$type}-".now()->format('Ymd-His').'.xlsx');
    }

    /** @return array{0: ?string, 1: ?string} */
    private function range(Request $request): array
    {
        return [$request->query('from'), $request->query('to')];
    }

    private function buildData(string $type, ?string $from, ?string $to): array
    {
        $period = ['from' => $from, 'to' => $to, 'generatedAt' => now()];

        return match ($type) {
            'books' => $period + [
                'title' => 'Laporan Data Buku',
                'books' => Book::with(['category', 'author', 'publisher'])->orderBy('judul')->get(),
            ],
            'students' => $period + [
                'title' => 'Laporan Data Anggota',
                'students' => User::role(\App\Enums\RoleName::Anggota->value)->with('mahasiswaProfile')->orderBy('name')->get(),
            ],
            'loans' => $period + [
                'title' => 'Laporan Peminjaman',
                'loans' => Loan::with(['user', 'details.book'])
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->latest()->get(),
            ],
            'fines' => $period + [
                'title' => 'Laporan Denda',
                'fines' => Fine::with(['user', 'loan'])
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->latest()->get(),
            ],
            'renewals' => $period + [
                'title' => 'Laporan Perpanjangan Kartu Anggota',
                'renewals' => \App\Models\CardRenewal::with(['user.mahasiswaProfile', 'petugas'])
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->latest()->get(),
            ],
        };
    }
}
