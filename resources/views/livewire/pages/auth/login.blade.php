<?php

use App\Livewire\Concerns\WithCaptcha;
use App\Livewire\Forms\LoginForm;
use App\Models\BlockedIp;
use App\Models\IpClearance;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    use WithCaptcha;

    public LoginForm $form;

    /** IP saat ini ditandai (banyak percobaan), belum diblokir, dan belum dibebaskan. */
    public bool $ipSuspicious = false;

    public function mount(): void
    {
        $ip = request()->ip();
        $this->ipSuspicious = ! BlockedIp::isBlocked($ip)
            && ! IpClearance::isCleared($ip)
            && LoginAttempt::whereDate('created_at', today())->where('ip_address', $ip)->count() > 5;
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        // Verifikasi captcha lebih dulu.
        $this->assertCaptcha();

        $this->validate();

        $this->form->authenticate();

        // Bila akun mengaktifkan verifikasi dua langkah, tunda login & minta kode OTP.
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user->twoFactorEnabled()) {
            $remember = $this->form->remember;
            \Illuminate\Support\Facades\Auth::logout();

            session()->put('login.2fa.id', $user->id);
            session()->put('login.2fa.remember', $remember);

            $this->newCaptcha();
            $this->redirect(route('two-factor.login'), navigate: true);

            return;
        }

        Session::regenerate();
        $this->newCaptcha();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-emerald-900">Masuk ke Akun</h2>
        <p class="mt-1 text-sm text-gray-500">Silakan masuk untuk melanjutkan.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($ipSuspicious)
        <div class="mb-4 text-center" wire:ignore>
            <x-location-verify :email="$form->email" label="Buka" />
        </div>
    @endif

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Password" />

            <x-password-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Captcha -->
        <div class="mt-4">
            <x-captcha-field :a="$a" :b="$b" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Ingat saya</span>
            </label>
        </div>

        <div class="mt-6">
            <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                Masuk
            </button>
        </div>

        @if (Route::has('password.request'))
            <p class="mt-3 text-center">
                <a class="text-sm text-gray-600 underline hover:text-gray-900" href="{{ route('password.request') }}" wire:navigate>Lupa password?</a>
            </p>
        @endif
    </form>

    <div class="mt-6 border-t pt-4 text-center text-sm text-gray-600">
        Belum punya akun?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-emerald-700 hover:underline">Daftar di sini</a>
    </div>
</div>
