# Tahap 1 — Analisis Sistem
## Sistem Informasi Perpustakaan Kampus

Dokumen ini adalah fondasi arsitektur. Semua tahap berikutnya (migration, model,
Livewire, transaksi, laporan, deployment) mengacu ke sini.

**Stack:** Laravel 13 · PHP 8.3+ · PostgreSQL · Breeze · Spatie Permission ·
Livewire 3 (Volt) · Tailwind CSS · Alpine.js · Vite · dompdf · maatwebsite/excel.

**Konvensi penamaan (campur):** kolom domain memakai Bahasa Indonesia
(`kode_buku`, `judul`, `tanggal_pinjam`, `tarif_denda`), sedangkan kolom
teknis/relasi/timestamp memakai konvensi Laravel (`id`, `*_id`, `created_at`,
`status`, `*_by`).

---

## 1. Aktor & Peran (Spatie Role)

| Aktor | Role | Ringkasan wewenang |
|-------|------|--------------------|
| **Super Admin** | `Super Admin` | Seluruh akses. Kelola user, role & permission, setting sistem, dashboard global. Mewarisi semua kemampuan Librarian. |
| **Pustakawan** | `Librarian` | Approval mahasiswa, CRUD master data, approve/tolak peminjaman, konfirmasi pengembalian, kelola denda, laporan. |
| **Staff** | `Staff` | Bantu transaksi: input peminjaman, lihat data buku. Tanpa akses approval/laporan/master sensitif. |
| **Mahasiswa** | `Mahasiswa` | Registrasi (pending), cari & pinjam buku, riwayat, status denda, profil. |

Pemisahan **role** (jabatan) dan **permission** (aksi granular) memakai Spatie.
Permission dicek di Policy & middleware, bukan hardcode role di view.

---

## 2. Use Case (per aktor)

### Mahasiswa
- UC-01 Registrasi akun + upload KTM (status awal `pending`)
- UC-02 Login / Logout
- UC-03 Kelola profil
- UC-04 Cari & lihat katalog buku (search realtime, filter kategori)
- UC-05 Ajukan peminjaman (status `pending`)
- UC-06 Lihat riwayat peminjaman
- UC-07 Lihat status denda
- UC-08 Terima notifikasi

### Staff
- UC-09 Lihat data buku
- UC-10 Input transaksi peminjaman (atas nama mahasiswa)
- UC-11 Bantu proses pengembalian

### Librarian (Pustakawan)
- UC-12 Approve / Reject pendaftaran mahasiswa + assign role `Mahasiswa`
- UC-13 CRUD master data (kategori, rak, penerbit, penulis, buku)
- UC-14 Approve / Reject pengajuan peminjaman → generate transaksi
- UC-15 Konfirmasi pengembalian + hitung denda otomatis
- UC-16 Kelola pembayaran denda
- UC-17 Generate laporan (PDF & Excel)
- UC-18 Dashboard operasional

### Super Admin
- UC-19 Semua use case Librarian
- UC-20 Kelola user (semua role)
- UC-21 Kelola role & permission
- UC-22 Dashboard & statistik global (grafik aktivitas)
- UC-23 Setting sistem (tarif denda, durasi pinjam, maksimal pinjam)

### System (otomatis)
- UC-24 Hitung denda otomatis = `jumlah_hari_telat × tarif_denda`
- UC-25 Generate `kode_buku` & `kode_pinjam`
- UC-26 Kirim notifikasi: akun diterima, peminjaman diterima, reminder jatuh tempo, terlambat
- UC-27 Jaga konsistensi stok (`stok_tersedia`) saat pinjam/kembali

---

## 3. Flow Sistem

### 3.1 Registrasi & Approval Mahasiswa
```
Mahasiswa → form registrasi (nama, NIM, email, password,
            fakultas, prodi, angkatan, no_hp, upload KTM)
        → simpan user (status = pending) + mahasiswa_profile
        → notifikasi masuk dashboard Librarian/Admin
Librarian → lihat daftar pendaftar → lihat detail
        ├── APPROVE → status = active, assignRole('Mahasiswa'),
        │             Notification "Akun diterima" → mahasiswa bisa login
        └── REJECT  → status = rejected (+ alasan), mahasiswa tidak bisa login
```
Login diblokir oleh middleware bila `status != active`.

### 3.2 Peminjaman
```
Mahasiswa → katalog → pilih buku → ajukan pinjam
        → loan (status = pending) + loan_detail(s)
        → notifikasi ke Librarian
Librarian → review pengajuan
        ├── APPROVE → cek stok → kurangi stok_tersedia
        │             set tanggal_pinjam = hari ini
        │             set tanggal_jatuh_tempo = +durasi (setting)
        │             status = dipinjam, approved_by = librarian
        │             Notification "Peminjaman diterima"
        └── REJECT  → status = ditolak (stok tidak berubah)
```

### 3.3 Pengembalian & Denda
```
Librarian → pilih loan aktif → konfirmasi pengembalian
        → buat return (tanggal_kembali, kondisi, returned_by)
        → tambah stok_tersedia kembali
        → hitung keterlambatan:
              hari_telat = max(0, tanggal_kembali - tanggal_jatuh_tempo)
              if hari_telat > 0:
                  total_denda = hari_telat × tarif_denda
                  buat fine (status = belum_bayar)
                  loan.status = terlambat
                  Notification "Terlambat"
              else:
                  loan.status = dikembalikan
Librarian → tandai denda lunas (paid_by, paid_at) saat dibayar
```

**Status loan:** `pending` · `dipinjam` · `dikembalikan` · `terlambat` · `ditolak`
**Status fine:** `belum_bayar` · `lunas` · `dibebaskan`

---

## 4. Entity Relationship (ringkas)

Entitas inti & kardinalitas (detail kolom lengkap ada di Tahap 2 / migration):

- `users` **1 — 1** `mahasiswa_profiles`
- `users` **1 — N** `loans` (peminjam)
- `categories` **1 — N** `books`
- `authors` **1 — N** `books`
- `publishers` **1 — N** `books`
- `shelves` **1 — N** `books`
- `loans` **1 — N** `loan_details` **N — 1** `books`
- `loans` **1 — 1** `returns`
- `loans` **1 — 1** `fines`
- `notifications.notifiable` → `users` (polymorphic, Laravel default)
- Spatie: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- `settings` (key–value) untuk konfigurasi runtime (tarif_denda, durasi_pinjam, max_pinjam)

> Catatan desain: spec tidak mencantumkan `shelf_id` di tabel buku, namun karena
> "Rak Buku" adalah master data, `books.shelf_id` ditambahkan (nullable) agar buku
> bisa dipetakan ke lokasi rak. Bila tidak diinginkan, mudah dihapus.

> `loan_details` memungkinkan **satu peminjaman berisi beberapa buku**. Bila
> kebijakan kampus 1 transaksi = 1 buku, struktur ini tetap valid (1 detail saja).

---

## 5. Struktur Folder (Clean Architecture)

```
perpustakaan-kampus/
├── app/
│   ├── Actions/                 # Business logic 1 use-case = 1 class
│   │   ├── Students/ApproveStudent.php, RejectStudent.php
│   │   ├── Loans/SubmitLoanRequest.php, ApproveLoan.php, RejectLoan.php
│   │   └── Returns/ProcessReturn.php
│   ├── Enums/                   # LoanStatus, UserStatus, FineStatus, RoleName
│   ├── Http/
│   │   ├── Controllers/         # tipis: auth (Breeze) + export laporan
│   │   ├── Middleware/          # EnsureAccountActive, role/permission
│   │   └── Requests/            # Form Request (validasi terpusat)
│   ├── Livewire/
│   │   ├── Admin/               # komponen khusus Super Admin
│   │   ├── Librarian/           # approval, CRUD, transaksi
│   │   ├── Student/             # katalog, ajukan pinjam, riwayat
│   │   └── Shared/              # Dashboard router, Toast, tabel reusable
│   ├── Models/
│   ├── Notifications/           # AccountApproved, LoanApproved, DueReminder, OverdueNotice
│   ├── Policies/
│   ├── Services/                # FineCalculator, StockService, ReportService, CodeGenerator
│   ├── Exports/                 # maatwebsite/excel: BooksExport, LoansExport
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── seeders/                 # RolePermissionSeeder, AdminSeeder, MasterDataSeeder, DemoSeeder
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── layouts/             # app (sidebar+navbar), guest
│   │   ├── livewire/            # blade tiap komponen
│   │   ├── components/          # blade UI: card, modal, toast, stat
│   │   └── reports/             # template Blade untuk PDF dompdf
│   ├── css/  └── js/
├── routes/
│   ├── web.php  ├── auth.php  └── console.php   # scheduler reminder
├── config/  ├── public/  ├── storage/app/public/{covers,ktm}
└── docs/                        # dokumen analisis & deployment
```

**Prinsip:** Controller & Livewire tipis → delegasi ke **Actions/Services**.
Validasi di **Form Request** / rules Livewire. Otorisasi di **Policy** +
middleware. Tidak ada query berat langsung di view.
