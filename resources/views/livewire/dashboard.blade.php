<?php

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Enums\UserStatus;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.dashboard')] class extends Component {
    public function with(): array
    {
        $user = auth()->user();

        if ($user->hasRole('Anggota')) {
            return ['view' => 'student'] + $this->studentData($user);
        }

        if ($user->hasRole('Staff')) {
            return ['view' => 'staff'] + $this->staffData();
        }

        return ['view' => 'admin'] + $this->adminData();
    }

    private function adminData(): array
    {
        // Aktivitas peminjaman 7 hari terakhir.
        $labels = [];
        $series = [];
        foreach (range(6, 0) as $i) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->translatedFormat('d M');
            $series[] = Loan::whereDate('created_at', $day)->count();
        }

        return [
            'totalUser' => User::count(),
            'judulBuku' => Book::count(),
            'totalTransaksi' => Loan::count(),
            'pinjamanAktif' => Loan::active()->count(),
            'pendingApproval' => User::where('status', UserStatus::Pending)->count(),
            'pendingLoan' => Loan::where('status', LoanStatus::Pending)->count(),
            'dendaBelum' => Fine::where('status', FineStatus::BelumBayar)->sum('total_denda'),
            'chartLabels' => $labels,
            'chartSeries' => $series,
        ];
    }

    private function staffData(): array
    {
        return [
            'judulBuku' => Book::count(),
            'stokTersedia' => Book::sum('stok_tersedia'),
            'pinjamanAktif' => Loan::active()->count(),
            'pendingLoan' => Loan::where('status', LoanStatus::Pending)->count(),
        ];
    }

    private function studentData(User $user): array
    {
        return [
            'aktif' => $user->loans()->active()->count(),
            'menunggu' => $user->loans()->where('status', LoanStatus::Pending)->count(),
            'riwayat' => $user->loans()->count(),
            'dendaBelum' => $user->fines()->where('status', FineStatus::BelumBayar)->sum('total_denda'),
        ];
    }
}; ?>

<div>
    @if ($view === 'admin')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Total User" :value="number_format($totalUser)" icon="users" color="emerald" />
            <x-stat-card label="Judul Buku" :value="number_format($judulBuku)" icon="book" color="sky" />
            <x-stat-card label="Total Transaksi" :value="number_format($totalTransaksi)" icon="swap" color="emerald" />
            <x-stat-card label="Pinjaman Aktif" :value="number_format($pinjamanAktif)" icon="clock" color="amber" />
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-xl border bg-white p-5 shadow-sm lg:col-span-2">
                <h3 class="mb-4 font-semibold text-gray-800">Aktivitas Peminjaman (7 hari terakhir)</h3>
                <div wire:ignore>
                    <canvas id="loanChart" height="110"
                            x-data
                            x-init="
                                const draw = () => {
                                    if (!window.Chart) return setTimeout(draw, 100);
                                    new Chart($el, {
                                        type: 'line',
                                        data: {
                                            labels: @js($chartLabels),
                                            datasets: [{
                                                label: 'Peminjaman',
                                                data: @js($chartSeries),
                                                borderColor: '#059669',
                                                backgroundColor: 'rgba(5,150,105,0.1)',
                                                fill: true, tension: 0.35, pointRadius: 3
                                            }]
                                        },
                                        options: { responsive: true, plugins: { legend: { display: false } },
                                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
                                    });
                                };
                                draw();
                            "></canvas>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Menunggu Persetujuan</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-3xl font-bold text-amber-600">{{ $pendingApproval }}</span>
                        <a href="{{ route('students.index') }}" class="text-sm font-medium text-emerald-600 hover:underline">Anggota &rarr;</a>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Pengajuan Pinjam Pending</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-3xl font-bold text-emerald-600">{{ $pendingLoan }}</span>
                        <a href="{{ route('loans.index') }}" class="text-sm font-medium text-emerald-600 hover:underline">Peminjaman &rarr;</a>
                    </div>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Denda Belum Dibayar</p>
                    <p class="mt-2 text-3xl font-bold text-rose-600">Rp {{ number_format($dendaBelum, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

    @elseif ($view === 'staff')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Judul Buku" :value="number_format($judulBuku)" icon="book" color="sky" />
            <x-stat-card label="Stok Tersedia" :value="number_format($stokTersedia)" icon="grid" color="emerald" />
            <x-stat-card label="Pinjaman Aktif" :value="number_format($pinjamanAktif)" icon="clock" color="amber" />
            <x-stat-card label="Pengajuan Pending" :value="number_format($pendingLoan)" icon="swap" color="emerald" />
        </div>
        <div class="mt-4 rounded-xl border bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-gray-800">Aksi Cepat</h3>
            <div class="mt-3 flex flex-wrap gap-3">
                <a href="{{ route('loans.index') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Input Peminjaman</a>
                <a href="{{ route('returns.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Proses Pengembalian</a>
                <a href="{{ route('books.index') }}" class="rounded-lg border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Lihat Data Buku</a>
            </div>
        </div>

    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Sedang Dipinjam" :value="$aktif" icon="book" color="emerald" />
            <x-stat-card label="Menunggu Approval" :value="$menunggu" icon="clock" color="amber" />
            <x-stat-card label="Total Riwayat" :value="$riwayat" icon="swap" color="sky" />
            <x-stat-card label="Denda" :value="'Rp '.number_format($dendaBelum, 0, ',', '.')" icon="cash" color="rose" />
        </div>
        <div class="mt-4 rounded-xl border bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-gray-800">Selamat datang, {{ auth()->user()->name }} 👋</h3>
            <p class="mt-1 text-sm text-gray-500">Cari dan pinjam buku favoritmu dari katalog perpustakaan.</p>
            <a href="{{ route('catalog') }}" class="mt-4 inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Jelajahi Katalog</a>
        </div>
    @endif
</div>
