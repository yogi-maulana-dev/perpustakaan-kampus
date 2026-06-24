<?php

use App\Enums\UserStatus;
use App\Models\Setting;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public int $lastSeen = 0;

    public function mount(): void
    {
        // Hindari toast untuk data yang sudah ada saat halaman pertama dibuka.
        $this->lastSeen = $this->pendingCount();
    }

    /** Pendaftar pending yang masuk dalam 1 jam terakhir (lebih lama otomatis hilang dari notif). */
    private function baseQuery()
    {
        return User::whereHas('mahasiswaProfile')
            ->where('status', UserStatus::Pending)
            ->where('created_at', '>=', now()->subHour());
    }

    private function pendingCount(): int
    {
        return $this->baseQuery()->count();
    }

    /** Dipanggil tiap interval (wire:poll). Pop toast hanya bila ada yang baru → tidak spam. */
    public function tick(): void
    {
        $current = $this->pendingCount();

        if ($current > $this->lastSeen) {
            $baru = $current - $this->lastSeen;
            $this->dispatch('toast', type: 'warning', message: "Ada {$baru} pendaftar anggota baru menunggu persetujuan.");
        }

        $this->lastSeen = $current;
    }

    #[Computed]
    public function pending()
    {
        return $this->baseQuery()->with('mahasiswaProfile')->latest()->limit(5)->get();
    }

    #[Computed]
    public function total(): int
    {
        return $this->pendingCount();
    }

    public function with(): array
    {
        return [
            'aktif' => (bool) Setting::get('notif_anggota_aktif', 1),
            'menit' => max(1, (int) Setting::get('notif_anggota_interval', 2)),
        ];
    }
}; ?>

<div x-data="{ open: false }" class="relative" @if ($aktif) wire:poll.{{ $menit * 60 }}s="tick" @endif>
    <button @click="open = !open" class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100" title="Pendaftar anggota baru">
        <x-icon name="user-check" class="h-6 w-6" />
        @if ($this->total > 0)
            <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white">{{ $this->total }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
         class="absolute right-0 mt-2 w-80 max-w-[90vw] rounded-lg border bg-white shadow-lg">
        <div class="flex items-center justify-between border-b px-4 py-2.5">
            <p class="text-sm font-semibold text-gray-700">Pendaftar Baru ({{ $this->total }})</p>
            <a href="{{ route('students.index', ['status' => 'pending']) }}" class="text-xs font-medium text-emerald-700 hover:underline">Lihat semua</a>
        </div>
        <div class="max-h-80 overflow-y-auto">
            @forelse ($this->pending as $p)
                <a href="{{ route('students.index', ['status' => 'pending']) }}" class="flex items-start gap-3 border-b px-4 py-3 hover:bg-gray-50">
                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">{{ strtoupper(substr($p->name, 0, 1)) }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-gray-800">{{ $p->name }}</span>
                        <span class="block truncate text-xs text-gray-500">{{ $p->mahasiswaProfile->tipe->label() }} · {{ $p->mahasiswaProfile->nomorIdentitas() ?? '-' }}</span>
                        <span class="mt-0.5 block text-[11px] text-gray-400">{{ $p->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-400">Tidak ada pendaftar baru (1 jam terakhir).</p>
            @endforelse
        </div>
        @if ($this->total > 5)
            <div class="border-t px-4 py-2 text-center text-xs text-gray-500">+{{ $this->total - 5 }} pendaftar lainnya</div>
        @endif
        @unless ($aktif)
            <div class="border-t px-4 py-2 text-center text-[11px] text-gray-400">Auto-refresh dinonaktifkan</div>
        @endunless
    </div>
</div>
