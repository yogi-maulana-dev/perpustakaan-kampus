<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public int $a = 0;
    public int $b = 0;
    public string $captcha = '';

    public function mount(): void
    {
        $this->newCaptcha();
    }

    /** Buat soal captcha baru & simpan jawabannya di session (tidak diekspos ke client). */
    public function newCaptcha(): void
    {
        $this->a = random_int(1, 9);
        $this->b = random_int(1, 9);
        $this->captcha = '';
        session(['login_captcha' => $this->a + $this->b]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        // Verifikasi captcha lebih dulu.
        if ((int) $this->captcha !== (int) session('login_captcha')) {
            $this->newCaptcha();

            throw ValidationException::withMessages([
                'captcha' => 'Jawaban verifikasi keamanan salah. Silakan coba lagi.',
            ]);
        }

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
            <x-input-label for="captcha" value="Verifikasi Keamanan" />
            <div class="mt-1 flex items-stretch gap-2">
                <span class="grid shrink-0 select-none place-items-center rounded-lg bg-emerald-100 px-4 text-base font-bold tracking-wider text-emerald-800 whitespace-nowrap">
                    {{ $a }} + {{ $b }} = ?
                </span>
                <x-text-input wire:model="captcha" id="captcha" class="block min-w-0 flex-1" type="text" inputmode="numeric" placeholder="Jawaban" required />
                <button type="button" wire:click="newCaptcha" title="Ganti soal"
                        class="grid w-11 shrink-0 place-items-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
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
