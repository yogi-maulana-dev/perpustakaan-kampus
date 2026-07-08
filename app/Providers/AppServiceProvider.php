<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Listeners\AuthActivitySubscriber;
use App\Models\Setting;
use App\Observers\ActivityObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin melewati seluruh pengecekan otorisasi.
        Gate::before(function ($user, $ability) {
            return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
        });

        $this->applyMailSettingsFromDatabase();

        // Catat aktivitas autentikasi (login/logout) + login_attempts.
        Event::subscribe(AuthActivitySubscriber::class);

        // Catat tambah/ubah/hapus data model bisnis penting.
        foreach ([
            \App\Models\Book::class,
            \App\Models\Category::class,
            \App\Models\Author::class,
            \App\Models\Publisher::class,
            \App\Models\Shelf::class,
            \App\Models\Loan::class,
            \App\Models\Fine::class,
            \App\Models\BookReturn::class,
            \App\Models\User::class,
            \App\Models\MahasiswaProfile::class,
            \App\Models\Slider::class,
            \App\Models\Pengurus::class,
            \App\Models\Ekatalog::class,
            \App\Models\BlockedIp::class,
        ] as $model) {
            $model::observe(ActivityObserver::class);
        }

        // Isi email reset password dapat diatur admin (Pengaturan).
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $email = $notifiable->getEmailForPasswordReset();
            $url = route('password.reset', ['token' => $token, 'email' => $email]);
            $subject = Setting::get('mail_reset_subject') ?: 'Atur Ulang Password — Perpustakaan UML';
            $body = Setting::get('mail_reset_body') ?: 'Anda menerima email ini karena ada permintaan reset password untuk akun Anda di Perpustakaan UML.';
            $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords', 'users').'.expire', 60);

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject($subject)
                ->greeting('Assalamu\'alaikum,')
                ->line($body)
                ->action('Atur Ulang Password', $url)
                ->line('Tautan ini berlaku selama '.$expire.' menit.')
                ->line('Jika Anda tidak merasa meminta reset password, abaikan email ini.')
                ->line('—')
                ->line('Didukung oleh Tim IT dan Pegawai Perpustakaan Universitas Muhammadiyah Lampung.')
                ->line('Dikembangkan oleh [yogi-maulana-dev](https://github.com/yogi-maulana-dev).')
                ->salutation('Terima kasih, Perpustakaan Universitas Muhammadiyah Lampung');
        });
    }

    /**
     * Timpa konfigurasi mail dengan setting dari admin (jika diisi).
     * Bila kosong, tetap memakai konfigurasi .env.
     */
    private function applyMailSettingsFromDatabase(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return; // mis. saat migrate pertama kali / DB belum siap
        }

        $host = Setting::get('mail_host');

        if (! $host) {
            return; // pakai .env
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) Setting::get('mail_port', 2525),
            'mail.mailers.smtp.username' => Setting::get('mail_username') ?: null,
            'mail.mailers.smtp.password' => Setting::get('mail_password') ?: null,
            'mail.mailers.smtp.encryption' => Setting::get('mail_encryption') ?: null,
            'mail.mailers.smtp.scheme' => Setting::get('mail_encryption') === 'ssl' ? 'smtps' : null,
            'mail.from.address' => Setting::get('mail_from_address') ?: config('mail.from.address'),
            'mail.from.name' => Setting::get('mail_from_name') ?: config('mail.from.name'),
        ]);
    }
}
