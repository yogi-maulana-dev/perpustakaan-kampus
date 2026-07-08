<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
    ];

    protected static function booted(): void
    {
        // Segarkan cache daftar IP terblokir setiap ada perubahan.
        static::saved(fn () => Cache::forget('blocked_ips'));
        static::deleted(fn () => Cache::forget('blocked_ips'));
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /** Daftar IP terblokir (dicache 60 detik) untuk pengecekan middleware yang cepat. */
    public static function blockedList(): array
    {
        return Cache::remember('blocked_ips', 60, fn () => static::pluck('ip_address')->all());
    }

    public static function isBlocked(?string $ip): bool
    {
        return $ip !== null && in_array($ip, static::blockedList(), true);
    }
}
