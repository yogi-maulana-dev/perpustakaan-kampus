<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\MahasiswaProfile;
use App\Models\Publisher;
use App\Models\Setting;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Setting sistem -----------------------------------------------------
        Setting::set('tarif_denda', 1000);      // per hari (Rp)
        Setting::set('durasi_pinjam', 7);        // hari
        Setting::set('max_pinjam', 3);           // maksimal pinjaman aktif
        Setting::set('perpanjangan_aktif', 1);   // 1 = boleh perpanjang
        Setting::set('max_perpanjangan', 2);     // maksimal kali perpanjangan
        Setting::set('notif_anggota_aktif', 1);  // 1 = tampilkan notif pendaftar baru
        Setting::set('notif_anggota_interval', 2); // refresh tiap N menit
        Setting::set('wa_number', '6281234567890'); // nomor WA perpustakaan (admin ganti di Pengaturan)
        Setting::set('wa_template', 'Assalamu\'alaikum, saya {nama} ({identitas}). Saya ingin mengajukan peminjaman buku "{judul}" (kode: {kode}). Mohon informasinya. Terima kasih 🙏');

        // Pengurus perpustakaan (demo) -------------------------------------
        foreach ([
            ['nama' => 'Dr. Hj. Aminah, M.Pd.', 'jabatan' => 'Kepala Perpustakaan', 'urutan' => 1],
            ['nama' => 'Budi Santoso, S.IP.', 'jabatan' => 'Pustakawan', 'urutan' => 2],
            ['nama' => 'Siti Rahma, S.Kom.', 'jabatan' => 'Staf Sirkulasi', 'urutan' => 3],
            ['nama' => 'Ahmad Fauzi, A.Md.', 'jabatan' => 'Staf Pengolahan', 'urutan' => 4],
            ['nama' => 'Dewi Lestari, S.Hum.', 'jabatan' => 'Staf Layanan Digital', 'urutan' => 5],
        ] as $data) {
            \App\Models\Pengurus::create($data);
        }

        // E-Katalog (demo) -------------------------------------------------
        foreach ([
            ['judul' => 'iPusnas', 'deskripsi' => 'Aplikasi perpustakaan digital nasional dari Perpustakaan Nasional RI — pinjam e-book gratis.', 'link' => 'https://ipusnas.id', 'urutan' => 1],
            ['judul' => 'Google Scholar', 'deskripsi' => 'Mesin pencari literatur akademik, jurnal, dan karya ilmiah.', 'link' => 'https://scholar.google.com', 'urutan' => 2],
            ['judul' => 'Garuda (Garba Rujukan Digital)', 'deskripsi' => 'Portal jurnal & publikasi ilmiah Indonesia (Kemdikbud).', 'link' => 'https://garuda.kemdikbud.go.id', 'urutan' => 3],
        ] as $data) {
            \App\Models\Ekatalog::create($data);
        }

        // Akun staf ----------------------------------------------------------
        // Super Admin: SATU-SATUNYA yang bisa membuka halaman Log Aktivitas/keamanan.
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@perpustakaan.test',
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        // Admin: pengelola perpustakaan penuh, TANPA akses Log Aktivitas.
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@perpustakaan.test',
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(RoleName::Admin->value);

        $librarian = User::create([
            'name' => 'Pustakawan',
            'email' => 'librarian@perpustakaan.test',
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);
        $librarian->assignRole(RoleName::Librarian->value);

        $staff = User::create([
            'name' => 'Staff Perpustakaan',
            'email' => 'staff@perpustakaan.test',
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);
        $staff->assignRole(RoleName::Staff->value);

        // Mahasiswa demo (aktif) --------------------------------------------
        $mhsAktif = User::create([
            'name' => 'Budi Mahasiswa',
            'email' => 'budi@student.test',
            'password' => Hash::make('password'),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);
        $mhsAktif->assignRole(RoleName::Anggota->value);
        MahasiswaProfile::create([
            'user_id' => $mhsAktif->id,
            'nim' => '2024010001',
            'fakultas' => 'Fakultas Teknik',
            'program_studi' => 'Informatika',
            'kode_prodi' => 'IF',
            'jenjang' => 'S1',
            'angkatan' => '2024',
            'no_hp' => '081234567890',
            'foto' => 'foto-anggota/demo.jpg',
        ]);

        // Mahasiswa demo (pending, untuk uji approval) ----------------------
        $mhsPending = User::create([
            'name' => 'Siti Pendaftar',
            'email' => 'siti@student.test',
            'password' => Hash::make('password'),
            'status' => UserStatus::Pending,
        ]);
        MahasiswaProfile::create([
            'user_id' => $mhsPending->id,
            'nim' => '2024010002',
            'fakultas' => 'Fakultas Ekonomi dan Bisnis',
            'program_studi' => 'Manajemen',
            'kode_prodi' => 'MNJ',
            'jenjang' => 'S1',
            'angkatan' => '2024',
            'no_hp' => '081298765432',
        ]);

        // Master data --------------------------------------------------------
        $categories = collect(['Teknologi', 'Sains', 'Sastra', 'Ekonomi', 'Sejarah'])
            ->map(fn ($nama) => Category::create(['nama' => $nama]));

        $authors = collect(['Andrea Hirata', 'Tere Liye', 'Raditya Dika', 'Pramoedya Ananta Toer'])
            ->map(fn ($nama) => Author::create(['nama' => $nama]));

        $publishers = collect(['Gramedia', 'Mizan', 'Erlangga', 'Bentang Pustaka'])
            ->map(fn ($nama) => Publisher::create(['nama' => $nama]));

        $shelves = collect([
            ['kode_rak' => 'A-01', 'lokasi' => 'Lantai 1 - Blok A'],
            ['kode_rak' => 'B-02', 'lokasi' => 'Lantai 1 - Blok B'],
            ['kode_rak' => 'C-03', 'lokasi' => 'Lantai 2 - Blok C'],
        ])->map(fn ($data) => Shelf::create($data));

        foreach (range(1, 20) as $i) {
            $stok = random_int(2, 6);
            Book::create([
                'kode_buku' => 'BK-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'isbn' => '978-602-'.random_int(1000, 9999).'-'.random_int(10, 99).'-'.random_int(0, 9),
                'judul' => 'Buku Contoh '.$i.' '.Str::title(fake()->words(2, true)),
                'category_id' => $categories->random()->id,
                'author_id' => $authors->random()->id,
                'publisher_id' => $publishers->random()->id,
                'shelf_id' => $shelves->random()->id,
                'tahun_terbit' => random_int(2010, 2024),
                'jumlah_stok' => $stok,
                'stok_tersedia' => $stok,
                'deskripsi' => fake()->paragraph(),
            ]);
        }
    }
}
