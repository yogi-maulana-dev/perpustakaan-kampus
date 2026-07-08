<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pembebasan IP dari status "ditandai" (mencurigakan) setelah pemiliknya
 * memverifikasi identitas via Google Authenticator atau OTP email 6 digit.
 */
class IpClearance extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'ip_address',
        'user_id',
        'email',
        'method',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** IP ini sedang dibebaskan (belum kedaluwarsa)? */
    public static function isCleared(?string $ip): bool
    {
        return $ip !== null
            && static::where('ip_address', $ip)->where('expires_at', '>', now())->exists();
    }
}
