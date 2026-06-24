<?php

use App\Actions\Loans\ApproveLoan;
use App\Actions\Loans\RejectLoan;
use App\Actions\Loans\SubmitLoanRequest;
use App\Enums\LoanStatus;
use App\Enums\RoleName;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'pending';

    public ?int $detailId = null;

    public bool $showReject = false;
    public ?int $rejectId = null;
    public string $rejectReason = '';

    // Input peminjaman (staf)
    public bool $showCreate = false;
    public ?int $form_user_id = null;
    public ?int $form_book_id = null;

    public bool $canManage = false;
    public bool $canInput = false;

    public function mount(): void
    {
        $this->canManage = auth()->user()->can('kelola peminjaman');
        $this->canInput = auth()->user()->can('input peminjaman');
        abort_unless($this->canManage || $this->canInput, 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status'])) {
            $this->resetPage();
        }
    }

    public function approve(int $id, ApproveLoan $action): void
    {
        $loan = Loan::findOrFail($id);
        $this->authorize('approve', $loan);
        try {
            $action->handle($loan, auth()->user());
            $this->detailId = null;
            $this->dispatch('toast', type: 'success', message: 'Peminjaman disetujui.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
        }
    }

    public function confirmReject(int $id): void
    {
        abort_unless($this->canManage, 403);
        $this->rejectId = $id;
        $this->rejectReason = '';
        $this->showReject = true;
    }

    public function reject(RejectLoan $action): void
    {
        $loan = Loan::findOrFail($this->rejectId);
        $this->authorize('approve', $loan);
        try {
            $action->handle($loan, auth()->user(), $this->rejectReason ?: null);
            $this->showReject = false;
            $this->detailId = null;
            $this->dispatch('toast', type: 'warning', message: 'Peminjaman ditolak.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
        }
    }

    public function openCreate(): void
    {
        abort_unless($this->canInput, 403);
        $this->reset(['form_user_id', 'form_book_id']);
        $this->resetValidation();
        $this->showCreate = true;
    }

    public function storeLoan(SubmitLoanRequest $action): void
    {
        abort_unless($this->canInput, 403);
        $this->validate([
            'form_user_id' => ['required', 'exists:users,id'],
            'form_book_id' => ['required', 'exists:books,id'],
        ]);

        $student = User::findOrFail($this->form_user_id);
        $book = Book::findOrFail($this->form_book_id);

        try {
            $action->handle($student, $book);
            $this->showCreate = false;
            $this->dispatch('toast', type: 'success', message: 'Pengajuan peminjaman dibuat.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
        }
    }

    public function with(): array
    {
        $query = Loan::with(['user', 'details.book', 'fine'])
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('kode_pinjam', 'ilike', "%{$this->search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$this->search}%"))))
            ->latest();

        return [
            'loans' => $query->paginate(10),
            'detail' => $this->detailId ? Loan::with(['user.mahasiswaProfile', 'details.book', 'approver'])->find($this->detailId) : null,
            'mahasiswaList' => $this->showCreate ? User::role(RoleName::Anggota->value)->where('status', 'active')->orderBy('name')->get() : collect(),
            'bookList' => $this->showCreate ? Book::where('stok_tersedia', '>', 0)->orderBy('judul')->get() : collect(),
            'statuses' => LoanStatus::cases(),
        ];
    }
}; ?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 gap-3">
            <div class="relative max-w-sm flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kode / nama mahasiswa…"
                       class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <select wire:model.live="status" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="pending">Menunggu</option>
                <option value="dipinjam">Dipinjam</option>
                <option value="terlambat">Terlambat</option>
                <option value="dikembalikan">Dikembalikan</option>
                <option value="ditolak">Ditolak</option>
                <option value="all">Semua</option>
            </select>
        </div>
        @if ($canInput)
            <button wire:click="openCreate" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <x-icon name="plus" class="h-4 w-4" /> Input Peminjaman
            </button>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Anggota</th>
                    <th class="px-4 py-3 hidden lg:table-cell">Buku</th>
                    <th class="px-4 py-3 hidden md:table-cell">Jatuh Tempo</th>
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
                        <td class="px-4 py-3 hidden md:table-cell">{{ $loan->tanggal_jatuh_tempo?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3"><x-badge :color="$loan->status->color()">{{ $loan->status->label() }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button wire:click="$set('detailId', {{ $loan->id }})" class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">Detail</button>
                                @if ($canManage && $loan->status === \App\Enums\LoanStatus::Pending)
                                    <button wire:click="approve({{ $loan->id }})" class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Approve</button>
                                    <button wire:click="confirmReject({{ $loan->id }})" class="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700">Reject</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $loans->links() }}</div>

    {{-- Detail modal --}}
    @if ($detail)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center">
            <div class="my-8 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Detail Peminjaman</h3>
                    <button wire:click="$set('detailId', null)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <dl class="grid grid-cols-3 gap-y-2 text-sm">
                    <dt class="text-gray-500">Kode</dt><dd class="col-span-2 font-mono">{{ $detail->kode_pinjam }}</dd>
                    <dt class="text-gray-500">Anggota</dt><dd class="col-span-2">{{ $detail->user->name }} ({{ $detail->user->mahasiswaProfile?->nomorIdentitas() }})</dd>
                    <dt class="text-gray-500">Status</dt><dd class="col-span-2"><x-badge :color="$detail->status->color()">{{ $detail->status->label() }}</x-badge></dd>
                    <dt class="text-gray-500">Tgl Pinjam</dt><dd class="col-span-2">{{ $detail->tanggal_pinjam?->format('d M Y') ?? '—' }}</dd>
                    <dt class="text-gray-500">Jatuh Tempo</dt><dd class="col-span-2">{{ $detail->tanggal_jatuh_tempo?->format('d M Y') ?? '—' }}</dd>
                    <dt class="text-gray-500">Disetujui</dt><dd class="col-span-2">{{ $detail->approver?->name ?? '—' }}</dd>
                </dl>
                <div class="mt-4">
                    <p class="mb-1 text-sm font-medium text-gray-700">Buku</p>
                    <ul class="list-inside list-disc text-sm text-gray-600">
                        @foreach ($detail->details as $d)
                            <li>{{ $d->book->judul }} (×{{ $d->jumlah }})</li>
                        @endforeach
                    </ul>
                </div>
                @if ($canManage && $detail->status === \App\Enums\LoanStatus::Pending)
                    <div class="mt-6 flex justify-end gap-2">
                        <button wire:click="confirmReject({{ $detail->id }})" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Reject</button>
                        <button wire:click="approve({{ $detail->id }})" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Approve</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Reject modal --}}
    @if ($showReject)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-800">Tolak Peminjaman</h3>
                <textarea wire:model="rejectReason" rows="3" class="mt-3 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Alasan (opsional)…"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('showReject', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                    <button wire:click="reject" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Tolak</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Create loan modal --}}
    @if ($showCreate)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Input Peminjaman</h3>
                <form wire:submit="storeLoan" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Anggota</label>
                        <select wire:model="form_user_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($mahasiswaList as $m) <option value="{{ $m->id }}">{{ $m->name }}</option> @endforeach
                        </select>
                        @error('form_user_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Buku</label>
                        <select wire:model="form_book_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($bookList as $b) <option value="{{ $b->id }}">{{ $b->judul }} (stok {{ $b->stok_tersedia }})</option> @endforeach
                        </select>
                        @error('form_book_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t pt-4">
                        <button type="button" wire:click="$set('showCreate', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Buat</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
