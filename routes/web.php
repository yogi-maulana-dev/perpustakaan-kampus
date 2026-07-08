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

// Detail buku publik (bisa dilihat sebelum login) — pakai UUID agar id tidak bisa ditebak.
Route::get('koleksi/{book:uuid}', function (\App\Models\Book $book) {
    $book->load(['author', 'publisher', 'category', 'shelf']);

    $related = \App\Models\Book::with('author')
        ->where('category_id', $book->category_id)
        ->where('id', '!=', $book->id)
        ->latest()->take(6)->get();

    return view('books.show', ['book' => $book, 'related' => $related]);
})->name('books.public');

// Manual book / panduan penggunaan untuk anggota (publik).
Route::view('panduan-anggota', 'panduan-anggota')->name('panduan.anggota');

// Profil perpustakaan (publik).
Route::view('profil/visi-misi', 'profil.visi-misi')->name('profil.visi-misi');
Route::view('profil/sejarah', 'profil.sejarah')->name('profil.sejarah');
Route::view('profil/struktur-organisasi', 'profil.struktur-organisasi')->name('profil.struktur-organisasi');

// Berbagi lokasi (dari halaman login saat IP mencurigakan / halaman Akses Dibatasi).
Route::post('verifikasi-lokasi', [\App\Http\Controllers\LocationController::class, 'store'])
    ->middleware('throttle:10,1')->name('location.share');

// Pemberitahuan keanggotaan kadaluarsa (di luar grup agar tidak saling redirect).
Route::get('keanggotaan-kadaluarsa', function () {
    $profile = auth()->user()->mahasiswaProfile;

    if (! $profile || ! $profile->kartuKadaluarsa()) {
        return redirect()->route('dashboard');
    }

    return view('membership-expired', ['profile' => $profile]);
})->middleware(['auth', 'active'])->name('membership.expired');

Route::middleware(['auth', 'active', 'member.valid', 'member.foto'])->group(function () {
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
        Volt::route('e-resources', 'admin.e-resources')->name('ekatalog.index');
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

    // ----- Administrasi (Super Admin & Admin) -----
    Route::middleware('role:Super Admin|Admin')->group(function () {
        Volt::route('profil', 'admin.profil')->name('profil.index');
        Volt::route('pengurus', 'admin.pengurus')->name('pengurus.index');
        Volt::route('kontak', 'admin.kontak')->name('kontak.index');
        Volt::route('users', 'admin.users')->name('users.index');
        Volt::route('pengaturan', 'admin.settings')->name('settings.index');
    });

    // ----- Keamanan & audit (khusus Super Admin) -----
    Route::middleware('role:Super Admin')->group(function () {
        Volt::route('log-aktivitas', 'admin.log-aktivitas')->name('log-aktivitas');
        Volt::route('tutorial-foto', 'admin.tutorial-foto')->name('tutorial-foto');

        Route::get('log-arsip/{name}', function (string $name) {
            $path = 'log-archives/'.basename($name); // basename → cegah path traversal
            abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

            return \Illuminate\Support\Facades\Storage::disk('local')->download($path);
        })->name('log.archive.download');
    });
});

require __DIR__.'/auth.php';
