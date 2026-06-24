<?php

use App\Enums\FineStatus;
use App\Models\ActivityLog;
use App\Models\Fine;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'belum_bayar';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('kelola denda'), 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status'])) {
            $this->resetPage();
        }
    }

    public function markPaid(int $id): void
    {
        $this->updateStatus($id, FineStatus::Lunas, 'fine.paid', 'melunasi');
        $this->dispatch('toast', type: 'success', message: 'Denda ditandai lunas.');
    }

    public function waive(int $id): void
    {
        $this->updateStatus($id, FineStatus::Dibebaskan, 'fine.waived', 'membebaskan');
        $this->dispatch('toast', type: 'success', message: 'Denda dibebaskan.');
    }

    private function updateStatus(int $id, FineStatus $status, string $action, string $verb): void
    {
        $fine = Fine::findOrFail($id);
        $fine->update([
            'status' => $status,
            'paid_at' => now(),
            'paid_by' => auth()->id(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => Fine::class,
            'subject_id' => $fine->id,
            'description' => ucfirst($verb)." denda #{$fine->id}",
            'ip_address' => request()->ip(),
        ]);
    }

    public function with(): array
    {
        return [
            'fines' => Fine::with(['user', 'loan'])
                ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
                ->when($this->search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$this->search}%")))
                ->latest()->paginate(10),
            'totalBelum' => Fine::where('status', FineStatus::BelumBayar)->sum('total_denda'),
        ];
    }
}; ?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 gap-3">
            <div class="relative max-w-sm flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama mahasiswa…"
                       class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <select wire:model.live="status" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="belum_bayar">Belum Bayar</option>
                <option value="lunas">Lunas</option>
                <option value="dibebaskan">Dibebaskan</option>
                <option value="all">Semua</option>
            </select>
        </div>
        <div class="rounded-lg bg-rose-50 px-4 py-2 text-sm text-rose-700">
            Belum dibayar: <span class="font-bold">Rp {{ number_format($totalBelum, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Anggota</th>
                    <th class="px-4 py-3">Kode Pinjam</th>
                    <th class="px-4 py-3">Hari Telat</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($fines as $fine)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $fine->user->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $fine->loan->kode_pinjam }}</td>
                        <td class="px-4 py-3">{{ $fine->jumlah_hari_telat }} hari</td>
                        <td class="px-4 py-3 font-medium">Rp {{ number_format($fine->total_denda, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><x-badge :color="$fine->status->color()">{{ $fine->status->label() }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @if ($fine->status === \App\Enums\FineStatus::BelumBayar)
                                    <button wire:click="markPaid({{ $fine->id }})" wire:confirm="Tandai denda ini LUNAS?" class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Lunas</button>
                                    <button wire:click="waive({{ $fine->id }})" wire:confirm="Bebaskan denda ini?" class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">Bebaskan</button>
                                @else
                                    <span class="text-xs text-gray-400">{{ $fine->paid_at?->format('d M Y') }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Tidak ada denda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $fines->links() }}</div>
</div>
