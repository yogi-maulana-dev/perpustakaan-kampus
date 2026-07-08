<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    protected $fillable = [
        'kode_pinjam',
        'user_id',
        'status',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'tanggal_kembali',
        'jumlah_perpanjangan',
        'approved_by',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'tanggal_pinjam' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'tanggal_kembali' => 'date',
            'jumlah_perpanjangan' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(LoanDetail::class);
    }

    public function return(): HasOne
    {
        return $this->hasOne(BookReturn::class);
    }

    public function fine(): HasOne
    {
        return $this->hasOne(Fine::class);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, LoanStatus::active(), true)
            && $this->tanggal_jatuh_tempo?->isPast();
    }

    /** Jumlah HARI PENUH keterlambatan sampai $asOf (default sekarang). 0 bila belum lewat tempo. */
    public function daysLate(?\Illuminate\Support\Carbon $asOf = null): int
    {
        $asOf ??= now();
        $due = $this->tanggal_jatuh_tempo;

        if (! $due) {
            return 0;
        }

        $dueDay = $due->copy()->startOfDay();
        $asDay = $asOf->copy()->startOfDay();

        return $asDay->greaterThan($dueDay) ? (int) $dueDay->diffInDays($asDay) : 0;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn ($s) => $s->value, LoanStatus::active()));
    }
}
