<?php

namespace App\Listeners;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/**
 * Mencatat aktivitas autentikasi: login berhasil, login gagal, logout.
 * Sekaligus menyimpan baris login_attempts (dipakai deteksi IP mencurigakan / brute force).
 */
class AuthActivitySubscriber
{
    public function handleLogin(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->recordAttempt($user->email, true, $user->id);
        ActivityLogger::log('login_success', 'Login berhasil', actor: $user, email: $user->email);
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->recordAttempt($email, false, $event->user?->getAuthIdentifier());
        ActivityLogger::log('login_failed', 'Login gagal untuk email: '.($email ?? '-'), actor: $event->user instanceof User ? $event->user : null, email: $email);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        if ($user) {
            ActivityLogger::log('logout', 'Logout', actor: $user);
        }
    }

    private function recordAttempt(?string $email, bool $successful, ?int $userId): void
    {
        $request = request();

        LoginAttempt::create([
            'user_id' => $userId,
            'email' => $email,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'successful' => $successful,
        ]);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
        ];
    }
}
