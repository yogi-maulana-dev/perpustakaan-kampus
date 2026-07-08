<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'email',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Label + warna ramah untuk badge di UI. */
    public function badgeColor(): string
    {
        return match (true) {
            str_contains($this->action, 'failed'), str_contains($this->action, 'suspicious') => 'rose',
            str_contains($this->action, 'deleted'), str_contains($this->action, 'blocked') => 'amber',
            str_contains($this->action, 'login'), str_contains($this->action, 'created') => 'emerald',
            default => 'zinc',
        };
    }
}
