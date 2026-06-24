<?php

use App\Enums\FineStatus;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        $user = auth()->user();

        return [
            'fines' => $user->fines()->with('loan')->latest()->paginate(10),
            'totalBelum' => $user->fines()->where('status', FineStatus::BelumBayar)->sum('total_denda'),
        ];
    }
}; ?>

<div>
    <div class="mb-4 rounded-xl border bg-white p-5 shadow-sm">
        <p class="text-sm text-gray-500">Total Denda Belum Dibayar</p>
        <p class="mt-1 text-3xl font-bold text-rose-600">Rp {{ number_format($totalBelum, 0, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-400">Lunasi denda di meja sirkulasi perpustakaan.</p>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode Pinjam</th>
                    <th class="px-4 py-3">Hari Telat</th>
                    <th class="px-4 py-3">Total Denda</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($fines as $fine)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $fine->loan->kode_pinjam }}</td>
                        <td class="px-4 py-3">{{ $fine->jumlah_hari_telat }} hari</td>
                        <td class="px-4 py-3 font-medium">Rp {{ number_format($fine->total_denda, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><x-badge :color="$fine->status->color()">{{ $fine->status->label() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">Tidak ada denda. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $fines->links() }}</div>
</div>
