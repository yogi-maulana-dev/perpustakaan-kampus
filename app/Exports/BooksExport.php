<?php

namespace App\Exports;

use App\Models\Book;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BooksExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Book::query()->with(['category', 'author', 'publisher'])->orderBy('judul');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Kode', 'ISBN', 'Judul', 'Kategori', 'Penulis', 'Penerbit', 'Tahun', 'Stok', 'Tersedia'];
    }

    /** @param Book $book */
    public function map($book): array
    {
        return [
            $book->kode_buku,
            $book->isbn,
            $book->judul,
            $book->category?->nama,
            $book->author?->nama,
            $book->publisher?->nama,
            $book->tahun_terbit,
            $book->jumlah_stok,
            $book->stok_tersedia,
        ];
    }
}
