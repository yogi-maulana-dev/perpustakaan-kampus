<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use PragmaRX\Google2FAQRCode\Google2FA;

new #[Layout('layouts.guest')] class extends Component
{
    public string $code = '';

    public function mount(): void
    {
        // Tanpa sesi login.2fa yang valid, kembalikan ke halaman masuk.
        if (! session('login.2fa.id') || ! User::find(session('login.2fa.id'))?->twoFactorEnabled()) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    public function verify(): void
    {
        $this->ensureIsNotRateLimited();

        $this->validate([
            'code' => ['required', 'string'],
        ], [], ['code' => 'kode']);

        $user = User::find(session('login.2fa.id'));

        if (! $user || ! $user->twoFactorEnabled()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $code = preg_replace('/\s+/', '', $this->code);
        $valid = (new Google2FA())->verifyKey($user->two_factor_secret, $code, 2);

        if (! $valid) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi salah atau kedaluwarsa. Coba lagi.',
            ]);
        }

        // Pengaman tambahan: hanya akun aktif yang boleh masuk.
        if ($user->status !== UserStatus::Active) {
            $this->reset2fa();
            $this->redirect(route('login'), navigate: true);

            return;
        }

        RateLimiter::clear($this->throttleKey());

        $remember = (bool) session('login.2fa.remember', false);
        Auth::loginUsingId($user->id, $remember);
        Session::regenerate();
        $this->reset2fa();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    private function reset2fa(): void
    {
        session()->forget(['login.2fa.id', 'login.2fa.remember']);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'code' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate('2fa|'.session('login.2fa.id').'|'.request()->ip());
    }

    public function cancel(): void
    {
        $this->reset2fa();
        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-emerald-700">
            <x-icon name="shield" class="h-6 w-6" />
        </span>
        <h2 class="text-xl font-bold text-emerald-900">Verifikasi Dua Langkah</h2>
        <p class="mt-1 text-sm text-gray-500">Masukkan 6 digit kode dari aplikasi Google Authenticator Anda.</p>
    </div>

    <form wire:submit="verify">
        <div>
            <x-input-label for="code" value="Kode Verifikasi" />
            <x-text-input wire:model="code" id="code" class="mt-1 block w-full text-center text-lg tracking-[0.4em]"
                          type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                          autocomplete="one-time-code" autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-6">
            <button type="submit" wire:loading.attr="disabled" wire:target="verify"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 disabled:opacity-60">
                <svg wire:loading wire:target="verify" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Verifikasi &amp; Masuk
            </button>
        </div>

        <p class="mt-4 text-center text-xs text-gray-500">
            Kehilangan akses ke aplikasi Authenticator? Hubungi pustakawan/admin untuk mereset.
        </p>

        <p class="mt-3 text-center">
            <button type="button" wire:click="cancel" class="text-sm text-gray-600 underline hover:text-gray-900">Kembali ke Masuk</button>
        </p>
    </form>
</div>
