<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Captcha matematika sederhana untuk form tamu (login, register, lupa password).
 * Jawaban disimpan di session — tidak diekspos ke client.
 */
trait WithCaptcha
{
    public int $a = 0;
    public int $b = 0;
    public string $captcha = '';

    /** Hook Livewire: otomatis dipanggil saat komponen di-mount. */
    public function mountWithCaptcha(): void
    {
        $this->newCaptcha();
    }

    /** Buat soal baru & simpan jawabannya di session. */
    public function newCaptcha(): void
    {
        $this->a = random_int(1, 9);
        $this->b = random_int(1, 9);
        $this->captcha = '';
        session(['auth_captcha' => $this->a + $this->b]);
    }

    /** Verifikasi jawaban; lempar error & buat soal baru bila salah. */
    protected function assertCaptcha(): void
    {
        if ((int) $this->captcha !== (int) session('auth_captcha')) {
            $this->newCaptcha();

            throw ValidationException::withMessages([
                'captcha' => 'Jawaban verifikasi keamanan salah. Silakan coba lagi.',
            ]);
        }
    }
}
