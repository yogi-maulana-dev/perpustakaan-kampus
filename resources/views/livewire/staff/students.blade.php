<?php

use App\Actions\Students\ApproveStudent;
use App\Actions\Students\RejectStudent;
use App\Enums\UserStatus;
use App\Exports\MembersTemplateExport;
use App\Imports\MembersImport;
use App\Models\MahasiswaProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts.dashboard')] class extends Component {
    use WithFileUploads, WithPagination;

    // Import Excel & upload foto massal
    public bool $showImport = false;
    public $importFile = null;
    public bool $showPhotos = false;
    public array $photos = [];

    public function downloadTemplate()
    {
        abort_unless(auth()->user()->can('approve mahasiswa'), 403);

        return Excel::download(new MembersTemplateExport, 'template-import-anggota.xlsx');
    }

    public function importExcel(): void
    {
        abort_unless(auth()->user()->can('approve mahasiswa'), 403);
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [], ['importFile' => 'berkas Excel']);

        $import = new MembersImport;
        Excel::import($import, $this->importFile); // .xlsx/.xls/.csv terdeteksi otomatis

        $this->reset('importFile', 'showImport');
        $this->resetPage();
        $this->dispatch('toast', type: 'success',
            message: "Import selesai: {$import->imported} anggota ditambahkan".($import->skipped ? ", {$import->skipped} dilewati (duplikat/tanpa nama)." : '.'));
    }

    public function uploadPhotos(): void
    {
        abort_unless(auth()->user()->can('approve mahasiswa'), 403);
        $this->validate([
            'photos.*' => ['image', 'max:2048'],
        ], [], ['photos.*' => 'foto']);

        $matched = 0;
        $unmatched = [];

        foreach ($this->photos as $file) {
            $name = strtolower(trim(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)));

            $profile = MahasiswaProfile::whereRaw('lower(nim) = ?', [$name])
                ->orWhereRaw('lower(nidn) = ?', [$name])
                ->orWhereRaw('lower(nbm) = ?', [$name])
                ->orWhereRaw('lower(nomor_identitas) = ?', [$name])
                ->first();

            if (! $profile) {
                // fallback: cocokkan bagian awal email (sebelum @) — lintas MySQL & PostgreSQL
                $localPart = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql'
                    ? "split_part(email, '@', 1)"
                    : "substring_index(email, '@', 1)";
                $user = User::whereRaw("lower({$localPart}) = ?", [$name])->first();
                $profile = $user?->mahasiswaProfile;
            }

            if (! $profile) {
                $unmatched[] = $name;
                continue;
            }

            if ($profile->foto) {
                Storage::disk('public')->delete($profile->foto);
            }
            $profile->update(['foto' => $file->store('foto-anggota', 'public')]);
            $matched++;
        }

        $this->reset('photos', 'showPhotos');
        $msg = "{$matched} foto terpasang.";
        if ($unmatched) {
            $msg .= ' Tidak cocok: '.implode(', ', array_slice($unmatched, 0, 5)).(count($unmatched) > 5 ? ', …' : '');
        }
        $this->dispatch('toast', type: $matched ? 'success' : 'warning', message: $msg);
    }

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

    /** Perpanjang masa berlaku kartu/keanggotaan sesuai Pengaturan. */
    public function perpanjangKartu(int $id): void
    {
        abort_unless(auth()->user()->can('approve mahasiswa'), 403);

        $user = User::with('mahasiswaProfile')->findOrFail($id);
        $profile = $user->mahasiswaProfile;
        abort_unless($profile, 404);

        $tahun = max(1, (int) \App\Models\Setting::get('masa_berlaku_kartu', 5));

        // Perpanjang dari tanggal berakhir sekarang (bila masih berlaku) atau dari hari ini (bila sudah lewat).
        $dari = $profile->kartuBerlakuSampai();
        $mulai = ($dari && $dari->isFuture()) ? $dari : now();
        $sampai = $mulai->copy()->addYears($tahun)->toDateString();

        $profile->update(['kartu_berlaku_sampai' => $sampai]);

        // Riwayat perpanjangan (untuk laporan & histori per anggota).
        \App\Models\CardRenewal::create([
            'user_id' => $user->id,
            'renewed_by' => auth()->id(),
            'dari_tanggal' => $dari?->toDateString(),
            'sampai_tanggal' => $sampai,
        ]);

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'member.card_renewed',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => "Perpanjang kartu {$user->name} s/d ".$profile->fresh()->kartu_berlaku_sampai->format('d-m-Y'),
            'ip_address' => request()->ip(),
        ]);

        $this->dispatch('toast', type: 'success',
            message: "Kartu {$user->name} diperpanjang s/d ".$profile->fresh()->kartu_berlaku_sampai->format('d-m-Y').'.');
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
                ->whereLike('name', "%{$this->search}%")
                ->orWhereLike('email', "%{$this->search}%")
                ->orWhereHas('mahasiswaProfile', fn ($p) => $p->whereLike('nim', "%{$this->search}%"))))
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

{{-- Realtime: daftar anggota diperbarui tiap 20 dtk saat tak ada modal/aksi terbuka. --}}
<div @if (! $detailId && ! $showReject && ! $showImport && ! $showPhotos) wire:poll.20s @endif>
    <div class="mb-3 flex flex-wrap justify-end gap-2">
        <button wire:click="$set('showImport', true)" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
            <x-icon name="download" class="h-4 w-4 rotate-180" /> Import Excel
        </button>
        <button wire:click="$set('showPhotos', true)" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
            <x-icon name="image" class="h-4 w-4" /> Foto Massal
        </button>
    </div>

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
                            @if ($s->mahasiswaProfile->kartuKadaluarsa())
                                <span class="mt-0.5 inline-block rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Kartu kadaluarsa</span>
                            @endif
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
                                    <button wire:click="perpanjangKartu({{ $s->id }})"
                                            wire:confirm="Perpanjang kartu {{ $s->name }}? Masa berlaku bertambah sesuai Pengaturan (saat ini berlaku s/d {{ $s->mahasiswaProfile->kartuBerlakuSampai()?->format('d-m-Y') ?? '-' }})."
                                            class="rounded-md border px-2.5 py-1 text-xs {{ $s->mahasiswaProfile->kartuKadaluarsa() ? 'border-rose-300 bg-rose-50 font-semibold text-rose-700 hover:bg-rose-100' : 'hover:bg-gray-50' }}">
                                        Perpanjang
                                    </button>
                                @endif
                                @if ($s->status === \App\Enums\UserStatus::Pending)
                                    <button wire:click="approve({{ $s->id }})" wire:confirm="Setujui pendaftaran {{ $s->name }}?" class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Approve</button>
                                    <button wire:click="confirmReject({{ $s->id }})" class="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-rose-700">Reject</button>
                                @elseif ($s->status === \App\Enums\UserStatus::Rejected)
                                    <button wire:click="approve({{ $s->id }})" wire:confirm="Setujui kembali {{ $s->name }} yang sebelumnya ditolak? Akun akan aktif dan bisa login." class="rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-emerald-700">Setujui Lagi</button>
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
                    <dt class="text-gray-500">Kartu Berlaku</dt>
                    <dd class="col-span-2">
                        s/d {{ $p->kartuBerlakuSampai()?->format('d-m-Y') ?? '-' }}
                        @if ($p->kartuKadaluarsa())
                            <x-badge color="rose">Kadaluarsa</x-badge>
                        @endif
                    </dd>
                    @php $riwayatPerpanjangan = \App\Models\CardRenewal::with('petugas')->where('user_id', $detail->id)->latest()->get(); @endphp
                    <dt class="text-gray-500">Perpanjangan</dt>
                    <dd class="col-span-2">
                        <x-badge color="emerald">{{ $riwayatPerpanjangan->count() }} kali</x-badge>
                        @if ($riwayatPerpanjangan->isNotEmpty())
                            <ul class="mt-1.5 space-y-1 text-xs text-gray-500">
                                @foreach ($riwayatPerpanjangan->take(5) as $r)
                                    <li>
                                        {{ $r->created_at->format('d-m-Y') }} —
                                        {{ $r->dari_tanggal?->format('d-m-Y') ?? 'awal' }} &rarr; <strong>{{ $r->sampai_tanggal->format('d-m-Y') }}</strong>
                                        · oleh {{ $r->petugas?->name ?? '—' }}
                                    </li>
                                @endforeach
                                @if ($riwayatPerpanjangan->count() > 5)
                                    <li class="text-gray-400">… dan {{ $riwayatPerpanjangan->count() - 5 }} lainnya (lihat Laporan Perpanjangan Kartu)</li>
                                @endif
                            </ul>
                        @endif
                    </dd>
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
                @if ($detail->status === \App\Enums\UserStatus::Rejected)
                    <div class="mt-6 flex justify-end">
                        <button wire:click="approve({{ $detail->id }})" wire:confirm="Setujui kembali {{ $detail->name }} yang sebelumnya ditolak?"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Setujui Lagi</button>
                    </div>
                @endif
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

    {{-- Import Excel anggota --}}
    @if ($showImport)
        <x-modal-overlay wire:click.self="$set('showImport', false)">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Import Anggota dari Excel</h3>
                    <button wire:click="$set('showImport', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>

                <div class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">
                    <ol class="list-inside list-decimal space-y-0.5 text-emerald-700">
                        <li>Unduh template, isi datanya (kolom <strong>Nama</strong> wajib).</li>
                        <li>Tipe: <code class="rounded bg-white px-1">mahasiswa</code> / <code class="rounded bg-white px-1">dosen</code> / <code class="rounded bg-white px-1">umum</code>.</li>
                        <li>Anggota hasil import langsung <strong>Aktif</strong>. Password = kolom Password, jika kosong pakai nomor identitas (atau <code class="rounded bg-white px-1">anggota123</code>).</li>
                        <li>Email kosong akan dibuat otomatis; baris dengan email yang sudah ada akan dilewati.</li>
                    </ol>
                    <button wire:click="downloadTemplate" class="mt-2 inline-flex items-center gap-1 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-300 hover:bg-emerald-100">
                        <x-icon name="download" class="h-4 w-4" /> Unduh Template (.xlsx)
                    </button>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Berkas Excel (.xlsx / .xls 2003 / .csv)</label>
                    <input wire:model="importFile" type="file" accept=".xlsx,.xls,.csv"
                           class="mt-1 w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:text-emerald-700" />
                    <div wire:loading wire:target="importFile" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                    @error('importFile') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t pt-4">
                    <button wire:click="$set('showImport', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                    <button wire:click="importExcel" wire:loading.attr="disabled" wire:target="importExcel,importFile"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                        <span wire:loading wire:target="importExcel" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Proses Import
                    </button>
                </div>
            </div>
        </x-modal-overlay>
    @endif

    {{-- Upload foto massal anggota --}}
    @if ($showPhotos)
        <x-modal-overlay wire:click.self="$set('showPhotos', false)">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Upload Foto Anggota Massal</h3>
                    <button wire:click="$set('showPhotos', false)" class="text-gray-400 hover:text-gray-600"><x-icon name="x" class="h-5 w-5" /></button>
                </div>

                <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                    Beri nama tiap foto sesuai <strong>NIM / NIDN / NBM / No. KTP</strong> anggota, contoh: <code class="rounded bg-white px-1">2024010001.jpg</code>.
                    (Alternatif: nama depan email sebelum <code>@</code>.) Rasio 3:4, maks 2&nbsp;MB.
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">Pilih banyak foto sekaligus</label>
                    <input wire:model="photos" type="file" accept="image/*" multiple
                           class="mt-1 w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:text-emerald-700" />
                    <div wire:loading wire:target="photos" class="mt-1 text-xs text-gray-500">Mengunggah…</div>
                    @error('photos.*') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    @if (count($photos))
                        <p class="mt-2 text-xs text-gray-500">{{ count($photos) }} berkas siap diproses.</p>
                    @endif
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t pt-4">
                    <button wire:click="$set('showPhotos', false)" class="rounded-lg border px-4 py-2 text-sm">Batal</button>
                    <button wire:click="uploadPhotos" wire:loading.attr="disabled" wire:target="uploadPhotos,photos"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
                        <span wire:loading wire:target="uploadPhotos" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        Pasang Foto
                    </button>
                </div>
            </div>
        </x-modal-overlay>
    @endif
</div>
