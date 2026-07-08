<?php

use App\Actions\Loans\MarkLoanLost;
use App\Actions\Returns\ProcessReturn;
use App\Models\Loan;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showReturn = false;
    public ?int $returnId = null;
    public string $tanggal_kembali = '';
    public string $kondisi = 'Baik';
    public string $catatan = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('kelola pengembalian'), 403);
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openReturn(int $id): void
    {
        $this->returnId = $id;
        $this->tanggal_kembali = now()->toDateString();
        $this->kondisi = 'Baik';
        $this->catatan = '';
        $this->resetValidation();
        $this->showReturn = true;
    }

    public function process(ProcessReturn $action): void
    {
        $this->validate([
            'tanggal_kembali' => ['required', 'date'],
            'kondisi' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $loanToReturn = Loan::findOrFail($this->returnId);
        $this->authorize('processReturn', $loanToReturn);

        try {
            $loan = $action->handle(
                $loanToReturn,
                auth()->user(),
                $this->tanggal_kembali,
                $this->kondisi,
                $this->catatan ?: null,
            );

            $this->showReturn = false;

            $msg = $loan->fine
                ? "Pengembalian dicatat. Denda Rp ".number_format($loan->fine->total_denda, 0, ',', '.')." ({$loan->fine->jumlah_hari_telat} hari telat)."
                : 'Pengembalian dicatat tepat waktu.';

            $this->dispatch('toast', type: $loan->fine ? 'warning' : 'success', message: $msg);
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
        }
    }

    public function markLost(int $id, MarkLoanLost $action): void
    {
        $loan = Loan::findOrFail($id);
        $this->authorize('markLost', $loan);

        try {
            $action->handle($loan, auth()->user());
            $this->dispatch('toast', type: 'warning', message: 'Peminjaman ditandai HILANG. Stok total buku berkurang.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
        }
    }

    public function with(): array
    {
        return [
            'loans' => Loan::with(['user', 'details.book'])
                ->active()
                ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                    ->whereLike('kode_pinjam', "%{$this->search}%")
                    ->orWhereHas('user', fn ($u) => $u->whereLike('name', "%{$this->search}%"))))
                ->orderBy('tanggal_jatuh_tempo')
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="mb-4 max-w-sm">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kode / nama mahasiswa…"
                   class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Anggota</th>
                    <th class="px-4 py-3 hidden lg:table-cell">Buku</th>
                    <th class="px-4 py-3">Jatuh Tempo</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($loans as $loan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $loan->kode_pinjam }}</td>
                        <td class="px-4 py-3">{{ $loan->user->name }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-gray-500">{{ $loan->details->pluck('book.judul')->implode(', ') }}</td>
                        <td class="px-4 py-3">
                            {{ $loan->tanggal_jatuh_tempo?->format('d M Y') }}
                            @if ($loan->isOverdue())
                                <span class="ml-1 text-xs font-medium text-rose-600">(lewat)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-badge :color="$loan->status->color()">{{ $loan->status->label() }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button wire:click="openReturn({{ $loan->id }})" class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Kembalikan</button>
                                <button wire:click="markLost({{ $loan->id }})" wire:confirm="Tandai buku ini HILANG? Stok total akan berkurang permanen." class="rounded-md border border-rose-200 px-2.5 py-1 text-xs text-rose-600 hover:bg-rose-50">Hilang</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Tidak ada peminjaman aktif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $loans->links() }}</div>

    @if ($showReturn)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Konfirmasi Pengembalian</h3>
                <form wire:submit="process" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Kembali</label>
                        <input wire:model="tanggal_kembali" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('tanggal_kembali') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kondisi Buku</label>
                        <select wire:model="kondisi" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option>Baik</option>
                            <option>Rusak Ringan</option>
                            <option>Rusak Berat</option>
                            <option>Hilang</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea wire:model="catatan" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    </div>
                    <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-700">Denda dihitung otomatis bila melewati jatuh tempo (tarif × jumlah hari telat).</p>
                    <div class="flex justify-end gap-2 border-t pt-4">
                        <button type="button" wire:click="$set('showReturn', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Proses</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
