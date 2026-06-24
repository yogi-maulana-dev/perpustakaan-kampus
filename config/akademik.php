<?php

/*
|--------------------------------------------------------------------------
| Data Akademik Universitas Muhammadiyah Lampung (UML)
|--------------------------------------------------------------------------
| Sumber: portal PMB resmi (uml.siakadcloud.com) + struktur fakultas UML.
| Kode prodi ("kode jurusan") di bawah dapat disesuaikan dengan kode resmi
| internal kampus bila berbeda. Dipakai untuk dropdown pendaftaran anggota.
|
| Catatan: pengelompokan fakultas untuk prodi baru (Hukum, Manajemen,
| D3 Kebidanan) bersifat menyesuaikan disiplin ilmu; ubah di sini bila perlu.
*/

return [

    'FT' => [
        'nama' => 'Fakultas Teknik',
        'prodi' => [
            ['kode' => 'IF', 'nama' => 'Informatika', 'jenjang' => 'S1'],
            ['kode' => 'TE', 'nama' => 'Teknik Elektro', 'jenjang' => 'S1'],
            ['kode' => 'TS', 'nama' => 'Teknik Sipil', 'jenjang' => 'S1'],
        ],
    ],

    'FAI' => [
        'nama' => 'Fakultas Agama Islam',
        'prodi' => [
            ['kode' => 'PAI', 'nama' => 'Pendidikan Agama Islam', 'jenjang' => 'S1'],
            ['kode' => 'ES', 'nama' => 'Ekonomi Syariah', 'jenjang' => 'S1'],
            ['kode' => 'PS', 'nama' => 'Perbankan Syariah', 'jenjang' => 'S1'],
        ],
    ],

    'FKIP' => [
        'nama' => 'Fakultas Keguruan dan Ilmu Pendidikan',
        'prodi' => [
            ['kode' => 'PBSI', 'nama' => 'Pendidikan Bahasa Indonesia', 'jenjang' => 'S1'],
            ['kode' => 'PBIG', 'nama' => 'Pendidikan Bahasa Inggris', 'jenjang' => 'S1'],
            ['kode' => 'PMTK', 'nama' => 'Pendidikan Matematika', 'jenjang' => 'S1'],
            ['kode' => 'PLB', 'nama' => 'Pendidikan Luar Biasa', 'jenjang' => 'S1'],
            ['kode' => 'SING', 'nama' => 'Sastra Inggris', 'jenjang' => 'S1'],
        ],
    ],

    'FISIP' => [
        'nama' => 'Fakultas Ilmu Sosial dan Ilmu Politik',
        'prodi' => [
            ['kode' => 'IKOM', 'nama' => 'Ilmu Komunikasi', 'jenjang' => 'S1'],
            ['kode' => 'IP', 'nama' => 'Ilmu Pemerintahan', 'jenjang' => 'S1'],
        ],
    ],

    'FPSI' => [
        'nama' => 'Fakultas Psikologi',
        'prodi' => [
            ['kode' => 'PSI', 'nama' => 'Psikologi', 'jenjang' => 'S1'],
        ],
    ],

    'FH' => [
        'nama' => 'Fakultas Hukum',
        'prodi' => [
            ['kode' => 'HK', 'nama' => 'Ilmu Hukum', 'jenjang' => 'S1'],
        ],
    ],

    'FEB' => [
        'nama' => 'Fakultas Ekonomi dan Bisnis',
        'prodi' => [
            ['kode' => 'MNJ', 'nama' => 'Manajemen', 'jenjang' => 'S1'],
        ],
    ],

    'FKES' => [
        'nama' => 'Fakultas Kesehatan',
        'prodi' => [
            ['kode' => 'KEB', 'nama' => 'Kebidanan', 'jenjang' => 'D3'],
        ],
    ],

];
