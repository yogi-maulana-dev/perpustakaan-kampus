<?php

use App\Actions\Loans\RenewLoan;
use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    public function renew(int $id, RenewLoan $action): void
    {
        $loan = Loan::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('renew', $loan);

        try {
            $loan = $action->handle($loan, auth()->user());
            $this->dispatch('toast', type: 'success', message: 'Peminjaman diperpanjang. Jatuh tempo baru: '.$loan->tanggal_jatuh_tempo->format('d M Y').'.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
        }
    }

    public function with(): array
    {
        return [
            'loans' => auth()->user()->loans()
                ->with(['details.book', 'fine'])
                ->latest()
                ->paginate(10),
            'perpanjanganAktif' => (bool) Setting::get('perpanjangan_aktif', 1),
            'maxPerpanjangan' => (int) Setting::get('max_perpanjangan', 2),
        ];
    }
}; ?>

<div>
    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Buku</th>
                    <th class="px-4 py-3 hidden md:table-cell">Pinjam</th>
                    <th class="px-4 py-3">Jatuh Tempo</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($loans as $loan)
                    @php
                        $bisaPerpanjang = $perpanjanganAktif
                            && $loan->status === \App\Enums\LoanStatus::Dipinjam
                            && $loan->tanggal_jatuh_tempo && ! $loan->tanggal_jatuh_tempo->isPast()
                            && $loan->jumlah_perpanjangan < $maxPerpanjangan
                            && ! ($loan->fine && $loan->fine->isUnpaid());
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $loan->kode_pinjam }}</td>
                        <td class="px-4 py-3">
                            @foreach ($loan->details as $d)
                                <p class="text-gray-800">{{ $d->book->judul }}</p>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">{{ $loan->tanggal_pinjam?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $loan->tanggal_jatuh_tempo?->format('d M Y') ?? '—' }}
                            @if ($loan->jumlah_perpanjangan > 0)
                                <span class="mt-0.5 block text-[11px] text-emerald-600">Diperpanjang {{ $loan->jumlah_perpanjangan }}x</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-badge :color="$loan->status->color()">{{ $loan->status->label() }}</x-badge>
                            @if ($loan->fine && $loan->fine->isUnpaid())
                                <span class="mt-1 block text-xs text-rose-600">Denda Rp {{ number_format($loan->fine->total_denda, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($bisaPerpanjang)
                                <button wire:click="renew({{ $loan->id }})" wire:confirm="Perpanjang masa pinjam buku ini?"
                                        class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">
                                    Perpanjang
                                </button>
                            @elseif ($loan->status === \App\Enums\LoanStatus::Dipinjam && $perpanjanganAktif && $loan->jumlah_perpanjangan >= $maxPerpanjangan)
                                <span class="text-xs text-gray-400">Batas perpanjangan</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada peminjaman.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $loans->links() }}</div>
</div>
