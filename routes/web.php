<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome', [
        'books' => \App\Models\Book::with('author')->latest()->take(10)->get(),
        'totalBuku' => \App\Models\Book::count(),
        'totalAnggota' => \App\Models\User::has('mahasiswaProfile')->count(),
        'totalKategori' => \App\Models\Category::count(),
        'totalPinjam' => \App\Models\Loan::count(),
        'sliders' => \App\Models\Slider::active()->get(),
        'pengurus' => \App\Models\Pengurus::active()->get(),
        'ekatalog' => \App\Models\Ekatalog::active()->get(),
    ]);
})->name('home');

Route::middleware(['auth', 'active', 'member.foto'])->group(function () {
    Volt::route('dashboard', 'dashboard')->name('dashboard');

    Volt::route('profile', 'profile.index')->name('profile');

    // Lengkapi foto (wajib bagi anggota sebelum melanjutkan)
    Volt::route('lengkapi-foto', 'student.upload-foto')->name('member.photo');

    // Kartu anggota (petugas atau pemilik akun) — otorisasi di controller
    Route::get('mahasiswa/kartu/cetak', [\App\Http\Controllers\MemberCardController::class, 'bulk'])->name('students.card.bulk');
    Route::get('mahasiswa/{user}/kartu', [\App\Http\Controllers\MemberCardController::class, 'show'])->name('students.card');

    // ----- Anggota (mahasiswa/dosen/umum) -----
    Route::middleware('role:Anggota')->group(function () {
        Volt::route('katalog', 'student.catalog')->name('catalog');
        Volt::route('pinjaman-saya', 'student.my-loans')->name('my-loans');
        Volt::route('denda-saya', 'student.my-fines')->name('my-fines');
    });

    // ----- Persetujuan mahasiswa -----
    Route::middleware('permission:approve mahasiswa')->group(function () {
        Volt::route('mahasiswa', 'staff.students')->name('students.index');
    });

    // ----- Data buku (lihat) -----
    Route::middleware('permission:lihat buku')->group(function () {
        Volt::route('buku', 'staff.books')->name('books.index');
    });

    // ----- Master data -----
    Route::middleware('permission:kelola master')->group(function () {
        Volt::route('kategori', 'staff.categories')->name('categories.index');
        Volt::route('penulis', 'staff.authors')->name('authors.index');
        Volt::route('penerbit', 'staff.publishers')->name('publishers.index');
        Volt::route('rak', 'staff.shelves')->name('shelves.index');
        Volt::route('slider', 'admin.sliders')->name('sliders.index');
        Volt::route('pengurus', 'admin.pengurus')->name('pengurus.index');
        Volt::route('e-katalog', 'admin.ekatalog')->name('ekatalog.index');
    });

    // ----- Transaksi (Tahap 5) -----
    Route::middleware('permission:input peminjaman')->group(function () {
        Volt::route('peminjaman', 'staff.loans')->name('loans.index');
    });
    Route::middleware('permission:kelola pengembalian')->group(function () {
        Volt::route('pengembalian', 'staff.returns')->name('returns.index');
    });
    Route::middleware('permission:kelola denda')->group(function () {
        Volt::route('denda', 'staff.fines')->name('fines.index');
    });

    // ----- Laporan (Tahap 6) -----
    Route::middleware('permission:lihat laporan')->group(function () {
        Volt::route('laporan', 'staff.reports')->name('reports.index');

        Route::middleware('permission:export laporan')->group(function () {
            Route::get('laporan/pdf/{type}', [ReportController::class, 'pdf'])->name('reports.pdf');
            Route::get('laporan/excel/{type}', [ReportController::class, 'excel'])->name('reports.excel');
        });
    });

    // ----- Administrasi -----
    Route::middleware('role:Super Admin')->group(function () {
        Volt::route('users', 'admin.users')->name('users.index');
        Volt::route('pengaturan', 'admin.settings')->name('settings.index');
    });
});

require __DIR__.'/auth.php';
