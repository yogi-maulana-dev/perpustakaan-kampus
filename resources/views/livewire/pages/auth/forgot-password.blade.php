<?php

use App\Livewire\Concerns\WithCaptcha;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    use WithCaptcha;

    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->assertCaptcha();

        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));

        $this->dispatch('reset-sent');
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        Lupa password? Tenang. Masukkan email akun Anda, lalu kami akan mengirimkan
        <strong>tautan reset password</strong> ke email tersebut untuk membuat password baru.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-captcha-field :a="$a" :b="$b" />
        </div>

        {{-- Progress bar selama proses kirim email --}}
        <div wire:loading wire:target="sendPasswordResetLink" class="mt-4">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-emerald-100">
                <div class="prog-bar h-full w-1/3 rounded-full bg-emerald-600"></div>
            </div>
            <p class="mt-2 text-center text-xs text-gray-500">Mengirim tautan reset ke email Anda… mohon tunggu sebentar.</p>
        </div>

        <div class="mt-6 flex items-center justify-between gap-3"
             x-data="{ cd: 0, start() { this.cd = 60; const t = setInterval(() => { if (--this.cd <= 0) clearInterval(t); }, 1000); } }"
             @reset-sent.window="start()">
            <a href="{{ route('login') }}" wire:navigate class="text-sm text-gray-600 underline hover:text-gray-900">Kembali ke Masuk</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="sendPasswordResetLink" :disabled="cd > 0"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60">
                <svg wire:loading wire:target="sendPasswordResetLink" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span wire:loading wire:target="sendPasswordResetLink">Mengirim…</span>
                <span wire:loading.remove wire:target="sendPasswordResetLink" x-show="cd === 0">Kirim Tautan Reset</span>
                <span wire:loading.remove wire:target="sendPasswordResetLink" x-show="cd > 0" x-cloak>Kirim ulang (<span x-text="cd"></span>s)</span>
            </button>
        </div>
    </form>

    <style>
        [x-cloak] { display: none !important; }
        @keyframes progSlide { 0% { transform: translateX(-110%); } 100% { transform: translateX(320%); } }
        .prog-bar { animation: progSlide 1.1s ease-in-out infinite; }
    </style>
</div>
