<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatat aktivitas terpusat. Menangkap identitas pelaku + konteks request
 * lalu menyimpannya ke tabel activity_logs.
 */
class ActivityLogger
{
    /**
     * @param  string       $action       kode aktivitas (login_success, book_created, dst)
     * @param  string|null  $description  detail / ringkasan perubahan
     * @param  Model|null   $subject      objek terkait (untuk subject_type & subject_id)
     * @param  User|null    $actor        pelaku (default: user login saat ini)
     */
    public static function log(string $action, ?string $description = null, ?Model $subject = null, ?User $actor = null, ?string $email = null): ActivityLog
    {
        $actor ??= Auth::user();
        $request = request();

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? 'Tamu',
            'user_role' => $actor?->getRoleNames()->first(),
            'email' => $email ?? $actor?->email,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
