<?php

use App\Actions\Students\ApproveStudent;
use App\Actions\Students\RejectStudent;
use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $tipe = '';

    public ?int $detailId = null;
    public bool $showReject = false;
    public ?int $rejectId = null;
    public string $rejectReason = '';
    public array $selected = [];

    public function toggleAllOnPage(array $ids): void
    {
        $this->selected = count($ids) && count(array_intersect($ids, $this->selected)) === count($ids)
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique(array_merge($this->selected, $ids)));
    }

    public function clearSelected(): void
    {
        $this->selected = [];
    }

    public function bulkApprove(ApproveStudent $action): void
    {
        $members = User::with('mahasiswaProfile')
            ->whereIn('id', $this->selected)
            ->where('status', UserStatus::Pending)
            ->get();

        foreach ($members as $m) {
            $action->handle($m, auth()->user());
        }

        $n = $members->count();
        $this->selected = [];
        $this->dispatch('toast', type: $n ? 'success' : 'warning',
            message: $n ? "{$n} anggota disetujui." : 'Tidak ada anggota berstatus menunggu yang terpilih.');
    }

    public function bulkReject(RejectStudent $action): void
    {
        $members = User::whereIn('id', $this->selected)
            ->where('status', UserStatus::Pending)
            ->get();

        foreach ($members as $m) {
            $action->handle($m, null, auth()->user());
        }

        $n = $members->count();
        $this->selected = [];
        $this->dispatch('toast', type: 'warning',
            message: $n ? "{$n} anggota ditolak." : 'Tidak ada anggota berstatus menunggu yang terpilih.');
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('approve mahasiswa'), 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status', 'tipe'])) {
            $this->resetPage();
        }
    }

    public function approve(int $id, ApproveStudent $action): void
    {
        $student = User::with('mahasiswaProfile')->findOrFail($id);
        $action->handle($student, auth()->user());
        $this->detailId = null;
        $this->dispatch('toast', type: 'success', message: "Pendaftaran {$student->name} disetujui.");
    }

    public function resetTwoFactor(int $id): void
    {
        abort_unless(auth()->user()->can('approve mahasiswa'), 403);

        $member = User::findOrFail($id);

        if (! $member->twoFactorEnabled()) {
            $this->dispatch('toast', type: 'warning', message: 'Anggota ini tidak mengaktifkan verifikasi dua langkah.');

            return;
        }

        $member->disableTwoFactor();
        $this->dispatch('toast', type: 'success', message: "Verifikasi dua langkah {$member->name} berhasil dinonaktifkan.");
    }

    public function confirmReject(int $id): void
    {
        $this->rejectId = $id;
        $this->rejectReason = '';
        $this->showReject = true;
    }

    public function reject(RejectStudent $action): void
    {
        $student = User::findOrFail($this->rejectId);
        $action->handle($student, $this->rejectReason ?: null, auth()->user());
        $this->showReject = false;
        $this->detailId = null;
        $this->dispatch('toast', type: 'warning', message: "Pendaftaran {$student->name} ditolak.");
    }

    public function with(): array
    {
        $query = User::query()
            ->whereHas('mahasiswaProfile')
            ->with('mahasiswaProfile')
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->tipe, fn ($q) => $q->whereHas('mahasiswaProfile', fn ($p) => $p->where('tipe', $this->tipe)))
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('name', 'ilike', "%{$this->search}%")
                ->orWhere('email', 'ilike', "%{$this->search}%")
                ->orWhereHas('mahasiswaProfile', fn ($p) => $p->where('nim', 'ilike', "%{$this->search}%"))))
            ->latest();

        $students = $query->paginate(10);

        return [
            'students' => $students,
            'detail' => $this->detailId ? User::with('mahasiswaProfile')->find($this->detailId) : null,
            'pageMemberIds' => $students->getCollection()
                ->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }
}; ?>

<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative max-w-sm flex-1">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama / NIM / email…"
                   class="w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>
        <select wire:model.live="tipe" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Tipe</option>
            <option value="mahasiswa">Mahasiswa</option>
            <option value="dosen">Dosen</option>
            <option value="umum">Umum</option>
        </select>
        <select wire:model.live="status" class="rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="all">Semua Status</option>
            <option value="pending">Menunggu</option>
            <option value="active">Aktif</option>
            <option value="rejected">Ditolak</option>
        </select>
    </div>

    @if (count($selected))
        <div class="mb-3 flex flex-wrap items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2.5 text-sm">
            <span class="mr-1 font-medium text-emerald-800">{{ count($selected) }} dipilih</span>
            <button wire:click="bulkApprove" wire:confirm="Setujui semua anggota terpilih yang masih menunggu?"
                    class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                <x-icon name="check-circle" class="h-4 w-4" /> Approve Terpilih
            </button>
            <button wire:click="bulkReject" wire:confirm="Tolak semua anggota terpilih yang masih menunggu?"
                    class="inline-flex items-center gap-1 rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                <x-icon name="x-circle" class="h-4 w-4" /> Tolak Terpilih
            </button>
            <a href="{{ route('students.card.bulk', ['ids' => implode(',', $selected)]) }}" target="_blank"
               class="inline-flex items-center gap-1 rounded-md border border-emerald-300 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                <x-icon name="doc" class="h-4 w-4" /> Cetak Kartu
            </a>
            <button wire:click="clearSelected" class="text-xs text-gray-500 hover:underline">Bersihkan</button>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full divide-y text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">
                        <input type="checkbox" wire:click="toggleAllOnPage({{ json_encode($pageMemberIds) }})"
                               @checked(count($pageMemberIds) && count(array_intersect($pageMemberIds, $selected)) === count($pageMemberIds))
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" title="Pilih semua anggota aktif di halaman ini">
                    </th>
                    <th class="px-4 py-3">Anggota</th>
                    <th class="px-4 py-3">Tipe / Identitas</th>
                    <th class="px-4 py-3">Jenjang</th>
                    <th class="px-4 py-3 hidden md:table-cell">Prodi / Instansi</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($students as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox" value="{{ $s->id }}" wire:model.live="selected"
                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">{{ $s->name }}</p>
                            <p class="text-xs text-gray-500">{{ $s->email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge color="emerald">{{ $s->mahasiswaProfile->tipe->label() }}</x-badge>
                            <span class="ml-1 text-gray-600">{{ $s->mahasiswaProfile->nomorIdentitas() ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($s->mahasiswaProfile->jenjang)
                                <x-badge color="emerald">{{ $s->mahasiswaProfile->jenjang }}</x-badge>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            @if ($s->mahasiswaProfile->program_studi)
                                {{ $s->mahasiswaProfile->jenjang ? $s->mahasiswaProfile->jenjang.' ' : '' }}{{ $s->mahasiswaProfile->program_studi }}
                            @else
                                {{ $s->mahasiswaProfile->instansi ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-badge :color="$s->status->color()">{{ $s->status->label() }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button wire:click="$set('detailId', {{ $s->id }})" class="rounded-md border px-2.5 py-1 text-xs hover:bg-gray-50">Detail</button>
                                @if ($s->status === \App\Enums\UserStatus::Active)
                                    <a href="{{ route('students.card', $s->id) }}" target="_blank" class="rounded-md border border-emerald-200 px-2.5 py-1 text-xs text-emerald-700 hover:bg-emerald-50">Kartu</a>
                                @endif
                                @if ($s->status === \App\Enums\UserStatus::Pending)
                                    <button wire:click="approve({{ $s->id }})" wire:confirm="Setujui pendaftaran {{ $s->name }}?" class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Approve</button>
                                    <button wire:click="confirmReject({{ $s->id }})" class="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700">Reject</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>

    {{-- Detail modal --}}
    @if ($detail)
        <x-modal-overlay wire:click.self="$set('detailId', null)">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Detail Pendaftar</h3>
                    <button wire:click="$set('detailId', null)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>
                <dl class="grid grid-cols-3 gap-y-3 text-sm">
                    @php $p = $detail->mahasiswaProfile; @endphp
                    <dt class="text-gray-500">Nama</dt><dd class="col-span-2 font-medium">{{ $detail->name }}</dd>
                    <dt class="text-gray-500">Tipe</dt><dd class="col-span-2">{{ $p->tipe->label() }}</dd>
                    <dt class="text-gray-500">{{ $p->tipe->idLabel() }}</dt><dd class="col-span-2">{{ $p->nomorIdentitas() ?? '—' }}</dd>
                    <dt class="text-gray-500">Email</dt><dd class="col-span-2">{{ $detail->email }}</dd>
                    @if ($p->fakultas)
                        <dt class="text-gray-500">Fakultas</dt><dd class="col-span-2">{{ $p->fakultas }}</dd>
                    @endif
                    @if ($p->jenjang)
                        <dt class="text-gray-500">Jenjang</dt><dd class="col-span-2"><x-badge color="emerald">{{ $p->jenjang }}</x-badge></dd>
                    @endif
                    @if ($p->program_studi)
                        <dt class="text-gray-500">Prodi</dt><dd class="col-span-2">{{ $p->jenjang ? $p->jenjang.' ' : '' }}{{ $p->program_studi }}@if ($p->kode_prodi) <span class="text-gray-400">({{ $p->kode_prodi }})</span>@endif</dd>
                    @endif
                    @if ($p->angkatan)
                        <dt class="text-gray-500">Angkatan</dt><dd class="col-span-2">{{ $p->angkatan }}</dd>
                    @endif
                    @if ($p->pekerjaan)
                        <dt class="text-gray-500">Pekerjaan</dt><dd class="col-span-2">{{ $p->pekerjaan }}</dd>
                    @endif
                    @if ($p->instansi)
                        <dt class="text-gray-500">Instansi</dt><dd class="col-span-2">{{ $p->instansi }}</dd>
                    @endif
                    @if ($p->nbm)
                        <dt class="text-gray-500">NBM</dt><dd class="col-span-2">{{ $p->nbm }}</dd>
                    @endif
                    <dt class="text-gray-500">No HP</dt><dd class="col-span-2">{{ $p->no_hp }}</dd>
                    <dt class="text-gray-500">Kartu Identitas</dt>
                    <dd class="col-span-2">
                        @if ($p->ktm_path)
                            @php
                                $ktmUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($p->ktm_path);
                                $isPdf = \Illuminate\Support\Str::endsWith(strtolower($p->ktm_path), '.pdf');
                            @endphp
                            @if ($isPdf)
                                <a href="{{ $ktmUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-emerald-600 hover:underline">
                                    📄 Lihat berkas PDF &rarr;
                                </a>
                            @else
                                <a href="{{ $ktmUrl }}" target="_blank" rel="noopener" class="block">
                                    <img src="{{ $ktmUrl }}" alt="Kartu Identitas" class="max-h-52 w-auto rounded-lg border object-contain hover:opacity-90" />
                                    <span class="mt-1 block text-xs text-emerald-600 hover:underline">Buka gambar penuh &rarr;</span>
                                </a>
                            @endif
                        @else
                            <span class="text-gray-400">Tidak ada berkas</span>
                        @endif
                    </dd>
                    <dt class="text-gray-500">Verifikasi 2 Langkah</dt>
                    <dd class="col-span-2">
                        @if ($detail->twoFactorEnabled())
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge color="emerald">Aktif</x-badge>
                                <button wire:click="resetTwoFactor({{ $detail->id }})"
                                        wire:confirm="Nonaktifkan / reset verifikasi dua langkah milik {{ $detail->name }}? Anggota dapat mengaktifkannya kembali dari halaman profil."
                                        class="rounded-md border border-rose-200 px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                    Reset / Matikan 2FA
                                </button>
                            </div>
                        @else
                            <span class="text-gray-400">Nonaktif</span>
                        @endif
                    </dd>
                </dl>
                @if ($detail->status === \App\Enums\UserStatus::Pending)
                    <div class="mt-6 flex justify-end gap-2">
                        <button wire:click="confirmReject({{ $detail->id }})" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Reject</button>
                        <button wire:click="approve({{ $detail->id }})" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Approve</button>
                    </div>
                @endif
            </div>
        </x-modal-overlay>
    @endif

    {{-- Reject modal --}}
    @if ($showReject)
        <x-modal-overlay wire:click.self="$set('showReject', false)">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-800">Tolak Pendaftaran</h3>
                <p class="mt-1 text-sm text-gray-500">Berikan alasan penolakan (opsional).</p>
                <textarea wire:model="rejectReason" rows="3" class="mt-3 w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Alasan…"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('showReject', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                    <button wire:click="reject" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Tolak</button>
                </div>
            </div>
        </x-modal-overlay>
    @endif
</div>
