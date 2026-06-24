<?php

use PragmaRX\Google2FAQRCode\Google2FA;
use Livewire\Volt\Component;

new class extends Component {
    /** Sedang dalam proses aktivasi (QR ditampilkan, menunggu konfirmasi kode). */
    public bool $enabling = false;

    /** Secret sementara selama proses aktivasi (belum disimpan permanen). */
    public string $pendingSecret = '';

    /** Kode 6 digit dari aplikasi Authenticator. */
    public string $code = '';

    /** Password untuk menonaktifkan 2FA. */
    public bool $confirmingDisable = false;
    public string $currentPassword = '';

    private function engine(): Google2FA
    {
        return new Google2FA();
    }

    /** Mulai proses aktivasi: buat secret baru & tampilkan QR. */
    public function enable(): void
    {
        $this->resetValidation();
        $this->pendingSecret = $this->engine()->generateSecretKey();
        $this->code = '';
        $this->enabling = true;
    }

    public function cancelEnable(): void
    {
        $this->reset('enabling', 'pendingSecret', 'code');
        $this->resetValidation();
    }

    /** Konfirmasi kode dari authenticator lalu simpan 2FA. */
    public function confirmEnable(): void
    {
        $this->validate([
            'code' => ['required', 'digits:6'],
        ], [], ['code' => 'kode']);

        $valid = $this->engine()->verifyKey($this->pendingSecret, $this->code, 2);

        if (! $valid) {
            $this->addError('code', 'Kode tidak valid. Pastikan waktu perangkat Anda tepat, lalu coba lagi.');

            return;
        }

        $user = auth()->user();
        $user->forceFill([
            'two_factor_secret' => $this->pendingSecret,
            'two_factor_enabled_at' => now(),
        ])->save();

        $this->reset('enabling', 'pendingSecret', 'code');
        $this->dispatch('toast', type: 'success', message: 'Verifikasi dua langkah berhasil diaktifkan.');
    }

    public function startDisable(): void
    {
        $this->resetValidation();
        $this->currentPassword = '';
        $this->confirmingDisable = true;
    }

    public function cancelDisable(): void
    {
        $this->reset('confirmingDisable', 'currentPassword');
        $this->resetValidation();
    }

    /** Nonaktifkan 2FA setelah verifikasi password. */
    public function disable(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
        ], [], ['currentPassword' => 'password']);

        auth()->user()->disableTwoFactor();

        $this->reset('confirmingDisable', 'currentPassword');
        $this->dispatch('toast', type: 'success', message: 'Verifikasi dua langkah telah dinonaktifkan.');
    }

    public function with(): array
    {
        $user = auth()->user();
        $qr = null;

        if ($this->enabling && $this->pendingSecret) {
            $qr = $this->engine()->getQRCodeInline(
                config('app.name'),
                $user->email,
                $this->pendingSecret,
            );
        }

        return [
            'enabled' => $user->twoFactorEnabled(),
            'qr' => $qr,
        ];
    }
}; ?>

<div>
    <header class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Verifikasi Dua Langkah (Google Authenticator)</h2>
            <p class="mt-1 text-sm text-gray-600">
                Tambahkan lapisan keamanan ekstra. Saat masuk, Anda perlu memasukkan kode dari aplikasi
                <span class="font-medium">Google Authenticator</span> (atau sejenisnya) di ponsel Anda.
            </p>
        </div>
        @if ($enabled)
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
            </span>
        @else
            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Nonaktif
            </span>
        @endif
    </header>

    {{-- STATE: belum aktif & tidak sedang mengaktifkan --}}
    @if (! $enabled && ! $enabling)
        <div class="mt-6">
            <button wire:click="enable" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                <x-icon name="shield" class="h-4 w-4" /> Aktifkan
            </button>
        </div>
    @endif

    {{-- STATE: proses aktivasi (scan QR + konfirmasi) --}}
    @if ($enabling)
        <div class="mt-6 grid gap-6 sm:grid-cols-[auto,1fr]">
            <div class="flex flex-col items-center">
                <div class="rounded-xl border bg-white p-3 shadow-sm">
                    <img src="{{ $qr }}" alt="QR Code Google Authenticator" class="h-44 w-44" />
                </div>
                <p class="mt-2 text-center text-xs text-gray-500">Scan dengan Google Authenticator</p>
            </div>
            <div>
                <ol class="list-inside list-decimal space-y-1 text-sm text-gray-600">
                    <li>Buka aplikasi <strong>Google Authenticator</strong> di ponsel.</li>
                    <li>Tap <strong>+</strong> &rarr; <strong>Scan kode QR</strong>.</li>
                    <li>Atau masukkan kunci ini secara manual:</li>
                </ol>
                <div class="mt-2 select-all rounded-lg bg-gray-50 px-3 py-2 font-mono text-sm tracking-wider text-gray-800">
                    {{ $pendingSecret }}
                </div>

                <div class="mt-4 max-w-xs">
                    <x-input-label for="tfa-code" value="Masukkan 6 digit kode" />
                    <x-text-input wire:model="code" wire:keydown.enter="confirmEnable" id="tfa-code" class="mt-1 block w-full tracking-widest"
                                  type="text" inputmode="numeric" maxlength="6" placeholder="000000" autocomplete="one-time-code" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button wire:click="confirmEnable" wire:loading.attr="disabled"
                            class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                        Verifikasi &amp; Aktifkan
                    </button>
                    <button wire:click="cancelEnable" class="text-sm text-gray-600 underline hover:text-gray-900">Batal</button>
                </div>
            </div>
        </div>
    @endif

    {{-- STATE: sudah aktif --}}
    @if ($enabled && ! $confirmingDisable)
        <div class="mt-6 rounded-lg bg-emerald-50 p-4">
            <p class="text-sm text-emerald-800">
                Verifikasi dua langkah <strong>aktif</strong>. Setiap kali masuk, Anda akan diminta kode dari aplikasi Authenticator.
            </p>
        </div>
        <div class="mt-4">
            <button wire:click="startDisable"
                    class="inline-flex items-center gap-2 rounded-lg border border-rose-300 px-5 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                Nonaktifkan
            </button>
            <p class="mt-2 text-xs text-gray-500">
                Kehilangan ponsel? Hubungi pustakawan/admin untuk mereset verifikasi dua langkah akun Anda.
            </p>
        </div>
    @endif

    {{-- STATE: konfirmasi nonaktif (password) --}}
    @if ($enabled && $confirmingDisable)
        <div class="mt-6 max-w-sm rounded-lg border border-rose-200 bg-rose-50/50 p-4">
            <p class="text-sm text-gray-700">Masukkan password Anda untuk menonaktifkan verifikasi dua langkah.</p>
            <div class="mt-3">
                <x-password-input wire:model="currentPassword" wire:keydown.enter="disable" id="tfa-disable-pass"
                                  class="block w-full" placeholder="Password saat ini" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('currentPassword')" class="mt-2" />
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button wire:click="disable" wire:loading.attr="disabled"
                        class="rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                    Nonaktifkan Sekarang
                </button>
                <button wire:click="cancelDisable" class="text-sm text-gray-600 underline hover:text-gray-900">Batal</button>
            </div>
        </div>
    @endif
</div>
