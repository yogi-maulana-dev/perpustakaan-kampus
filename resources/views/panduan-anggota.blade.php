<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panduan Anggota — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{background:#f0fdf4}</style>
</head>
<body class="font-sans text-gray-800 antialiased">

    {{-- ===== Navbar (komponen bersama — sama dengan halaman depan) ===== --}}
    @include('partials.public-nav', ['solid' => true])

    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
        {{-- Judul --}}
        <div class="text-center">
            <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Manual Book</span>
            <h1 class="mt-3 text-3xl font-extrabold text-emerald-900 sm:text-4xl">Panduan Anggota Perpustakaan</h1>
            <p class="mx-auto mt-3 max-w-2xl text-gray-500">
                Ikuti langkah-langkah berikut untuk menjadi anggota dan menggunakan layanan
                Perpustakaan Universitas Muhammadiyah Lampung — mulai dari mendaftar, meminjam buku,
                sampai mengembalikannya.
            </p>
        </div>

        {{-- Langkah-langkah --}}
        <div class="mt-10 space-y-5">
            @php
                $steps = [
                    [
                        'judul' => 'Daftar Akun Anggota',
                        'isi' => 'Klik tombol <strong>Daftar Sekarang</strong>, pilih jenis keanggotaan (<strong>Mahasiswa / Dosen / Umum</strong>), lalu lengkapi data diri: nama, email, NIM/NIDN/KTP sesuai tipe, fakultas & program studi, nomor HP, dan password.',
                        'tips' => 'Gunakan email aktif — email ini dipakai untuk reset password bila lupa.',
                    ],
                    [
                        'judul' => 'Unggah Kartu Identitas & Pas Foto 3×4',
                        'isi' => 'Masih di halaman pendaftaran, unggah <strong>kartu identitas</strong> (KTM/KTP/Kartu Dosen — JPG/PNG/PDF maks 2 MB) dan <strong>pas foto 3×4</strong> (JPG/PNG maks 2 MB). Pas foto dipakai untuk kartu anggota Anda.',
                        'tips' => 'Belum punya foto ukuran 3×4? Klik tombol <strong>"Lihat Tutorial Ganti Ukuran Foto"</strong> di halaman pendaftaran — ada video tutorial dan website untuk mengubah ukuran foto secara online.',
                    ],
                    [
                        'judul' => 'Tunggu Persetujuan Pustakawan',
                        'isi' => 'Setelah mendaftar, akun Anda berstatus <strong>menunggu persetujuan</strong>. Pustakawan akan memeriksa data dan kartu identitas Anda. Anda <strong>belum bisa login</strong> sebelum akun disetujui.',
                        'tips' => 'Proses ini biasanya selesai pada hari kerja. Bila lama, hubungi petugas perpustakaan.',
                    ],
                    [
                        'judul' => 'Login ke Sistem',
                        'isi' => 'Setelah disetujui, login dengan email dan password yang Anda daftarkan. Anda akan masuk ke <strong>dashboard anggota</strong> yang berisi menu <strong>Katalog Buku</strong>, <strong>Pinjaman Saya</strong>, dan <strong>Denda Saya</strong>.',
                        'tips' => 'Lupa password? Klik "Lupa password?" di halaman login — tautan reset dikirim ke email Anda.',
                    ],
                    [
                        'judul' => 'Cari & Ajukan Pinjam Buku',
                        'isi' => 'Buka menu <strong>Katalog Buku</strong>, cari judul yang diinginkan, lalu klik <strong>"Ajukan Pinjam"</strong>. Pengajuan Anda masuk ke petugas untuk diproses. Bisa juga mengajukan lewat tombol <strong>"Pinjam via WhatsApp"</strong> bila tersedia.',
                        'tips' => 'Jumlah buku yang bisa dipinjam bersamaan dibatasi sesuai ketentuan perpustakaan.',
                    ],
                    [
                        'judul' => 'Ambil Buku di Perpustakaan',
                        'isi' => 'Datang ke perpustakaan dengan membawa <strong>kartu anggota</strong>. Petugas memproses peminjaman dan memberi tahu <strong>tanggal jatuh tempo</strong> pengembalian. Status pinjaman bisa dipantau di menu <strong>Pinjaman Saya</strong>.',
                        'tips' => 'Sistem mengirim pengingat otomatis sehari sebelum jatuh tempo.',
                    ],
                    [
                        'judul' => 'Kembalikan / Perpanjang Tepat Waktu',
                        'isi' => 'Kembalikan buku ke petugas sebelum tanggal jatuh tempo. Bila masih dibutuhkan, ajukan <strong>perpanjangan</strong> (jika diizinkan) sebelum jatuh tempo — masa pinjam bertambah sesuai ketentuan.',
                        'tips' => 'Perpanjangan punya batas maksimal; setelah itu buku wajib dikembalikan.',
                    ],
                    [
                        'judul' => 'Denda Keterlambatan',
                        'isi' => 'Terlambat mengembalikan dikenakan <strong>denda per hari</strong> sesuai tarif yang berlaku. Rincian denda muncul otomatis di menu <strong>Denda Saya</strong>, dan pembayaran dilakukan ke petugas perpustakaan.',
                        'tips' => 'Denda terus bertambah setiap hari sampai buku dikembalikan — kembalikan tepat waktu ya.',
                    ],
                ];
            @endphp

            @foreach ($steps as $i => $step)
                <div class="flex gap-4 rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm sm:gap-5 sm:p-6">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-700 text-lg font-extrabold text-white sm:h-12 sm:w-12">{{ $i + 1 }}</div>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-emerald-900 sm:text-lg">{{ $step['judul'] }}</h2>
                        <p class="mt-1.5 text-sm leading-relaxed text-gray-600">{!! $step['isi'] !!}</p>
                        <p class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs leading-relaxed text-emerald-800">
                            <span class="font-bold">💡 Tips:</span> {!! $step['tips'] !!}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ajakan daftar --}}
        <div class="mt-10 rounded-2xl bg-gradient-to-r from-emerald-800 to-emerald-600 p-8 text-center text-white shadow-lg">
            <h2 class="text-xl font-extrabold sm:text-2xl">Sudah paham alurnya? Yuk jadi anggota!</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-emerald-100">Siapkan kartu identitas (KTM/KTP/Kartu Dosen) dan pas foto 3×4, lalu daftar — hanya butuh beberapa menit.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-full bg-yellow-300 px-6 py-3 text-sm font-extrabold text-emerald-950 shadow hover:bg-yellow-200">Daftar Sekarang</a>
                <a href="{{ route('login') }}" class="rounded-full bg-white/15 px-6 py-3 text-sm font-semibold text-white ring-1 ring-white/40 hover:bg-white/25">Sudah Punya Akun? Login</a>
            </div>
        </div>
    </main>

    <footer class="border-t border-emerald-100 bg-white py-6 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} Perpustakaan Universitas Muhammadiyah Lampung
    </footer>
</body>
</html>
