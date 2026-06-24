<?php

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.dashboard')] #[Title('Profil Saya')] class extends Component {
    public function with(): array
    {
        $user = auth()->user();

        return [
            'waNum' => Setting::waNumber(),
            'waUrl' => Setting::waUrl([
                'nama' => $user->name,
                'identitas' => $user->mahasiswaProfile?->nomorIdentitas() ?? '-',
                'judul' => '-',
                'kode' => '-',
            ]),
        ];
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    {{-- Header kartu --}}
    <div class="overflow-hidden rounded-xl border bg-gradient-to-r from-emerald-700 to-emerald-600 shadow-sm">
        <div class="flex items-center gap-4 p-6 text-white">
            <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-white/15 text-2xl font-bold ring-2 ring-white/30">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h2 class="truncate text-xl font-bold">{{ auth()->user()->name }}</h2>
                <p class="truncate text-sm text-emerald-100">{{ auth()->user()->email }}</p>
                <span class="mt-1 inline-block rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium text-emerald-50">
                    {{ auth()->user()->getRoleNames()->first() }}
                </span>
            </div>
        </div>
    </div>

    {{-- Bantuan WhatsApp --}}
    @if ($waNum)
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-green-100 text-green-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
                    </span>
                    <div>
                        <p class="font-semibold text-gray-800">Butuh bantuan?</p>
                        <p class="text-sm text-gray-500">Assalamu'alaikum 🙏 Hubungi perpustakaan via WhatsApp di <span class="font-medium text-emerald-700">+{{ $waNum }}</span></p>
                    </div>
                </div>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                   class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-green-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-600">
                    Chat via WhatsApp
                </a>
            </div>
        </div>
    @endif

    {{-- Informasi profil --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <div class="max-w-xl">
            <livewire:profile.update-profile-information-form />
        </div>
    </div>

    {{-- Verifikasi dua langkah (2FA) --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <livewire:profile.two-factor />
    </div>

    {{-- Ubah password --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <div class="max-w-xl">
            <livewire:profile.update-password-form />
        </div>
    </div>

    {{-- Hapus akun --}}
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <div class="max-w-xl">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</div>
