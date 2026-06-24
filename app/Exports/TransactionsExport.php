<?php

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private ?string $from = null, private ?string $to = null)
    {
    }

    public function query()
    {
        return Loan::query()
            ->with(['user', 'details.book', 'fine'])
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->latest();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Kode Pinjam', 'Anggota', 'Buku', 'Tgl Pinjam', 'Jatuh Tempo', 'Tgl Kembali', 'Status', 'Denda'];
    }

    /** @param Loan $loan */
    public function map($loan): array
    {
        return [
            $loan->kode_pinjam,
            $loan->user?->name,
            $loan->details->pluck('book.judul')->implode(', '),
            $loan->tanggal_pinjam?->format('Y-m-d'),
            $loan->tanggal_jatuh_tempo?->format('Y-m-d'),
            $loan->tanggal_kembali?->format('Y-m-d'),
            $loan->status->label(),
            $loan->fine?->total_denda ?? 0,
        ];
    }
}
