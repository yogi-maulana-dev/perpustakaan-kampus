<?php

namespace App\Models;

use App\Enums\FineStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    protected $fillable = [
        'loan_id',
        'user_id',
        'jumlah_hari_telat',
        'tarif_denda',
        'total_denda',
        'status',
        'paid_at',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => FineStatus::class,
            'jumlah_hari_telat' => 'integer',
            'tarif_denda' => 'integer',
            'total_denda' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isUnpaid(): bool
    {
        return $this->status === FineStatus::BelumBayar;
    }
}
