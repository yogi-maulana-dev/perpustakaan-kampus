<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Template kosong (+contoh baris) untuk import buku.
 * Kolom mengikuti data perpustakaan; hanya "Judul Buku" yang wajib.
 */
class BooksTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return ['No', 'Nama Pengarang', 'Judul Buku', 'Penerbit', 'Tahun Terbit', 'Cet/ED', 'Kategori', 'ISBN', 'Stok'];
    }

    public function array(): array
    {
        return [
            [1, 'Ubedilah Badrun', 'Sistem Politik Indonesia', 'Bumi Aksara', 2016, '1', 'Politik', '', 3],
            [2, 'Nurudin', 'Ilmu Komunikasi (Ilmiah dan Populer)', 'Rajawali Pers', 2017, '2', 'Komunikasi', '', 2],
        ];
    }

    public function title(): string
    {
        return 'Template Buku';
    }
}
