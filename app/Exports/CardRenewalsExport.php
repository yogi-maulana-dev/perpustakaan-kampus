<?php

namespace App\Exports;

use App\Models\CardRenewal;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CardRenewalsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private ?string $from = null, private ?string $to = null)
    {
    }

    public function query()
    {
        return CardRenewal::query()
            ->with(['user.mahasiswaProfile', 'petugas'])
            ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->latest();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Tanggal', 'Anggota', 'Email', 'No. Identitas', 'Berlaku Lama', 'Berlaku Baru', 'Diperpanjang Oleh'];
    }

    /** @param CardRenewal $r */
    public function map($r): array
    {
        return [
            $r->created_at?->format('Y-m-d H:i'),
            $r->user?->name,
            $r->user?->email,
            $r->user?->mahasiswaProfile?->nomorIdentitas(),
            $r->dari_tanggal?->format('Y-m-d'),
            $r->sampai_tanggal?->format('Y-m-d'),
            $r->petugas?->name,
        ];
    }
}
