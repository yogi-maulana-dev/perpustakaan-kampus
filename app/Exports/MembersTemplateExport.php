<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Template kosong (+contoh baris) untuk import anggota.
 * Hanya "Nama" yang wajib. Tipe: mahasiswa/dosen/umum.
 */
class MembersTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'Nama', 'Email', 'Tipe', 'NIM', 'NIDN', 'NBM', 'Nomor Identitas',
            'Fakultas', 'Program Studi', 'Kode Prodi', 'Jenjang', 'Angkatan',
            'Pekerjaan', 'Instansi', 'No HP', 'Password',
        ];
    }

    public function array(): array
    {
        return [
            ['Budi Santoso', 'budi@student.uml.ac.id', 'mahasiswa', '2024010001', '', '', '', 'Fakultas Teknik', 'Informatika', 'IF', 'S1', '2024', '', '', '081234567890', ''],
            ['Dr. Ahmad', 'ahmad@uml.ac.id', 'dosen', '', '0012345678', '', '', 'Fakultas Teknik', 'Teknik Elektro', 'TE', 'S1', '', 'Dosen', '', '081200000001', ''],
            ['Siti Warga', 'siti@gmail.com', 'umum', '', '', '', '1871xxxxxxxx', '', '', '', '', '', 'Wiraswasta', 'Toko Siti', '081200000002', ''],
        ];
    }

    public function title(): string
    {
        return 'Template Anggota';
    }
}
