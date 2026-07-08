<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardRenewal extends Model
{
    protected $fillable = ['user_id', 'renewed_by', 'dari_tanggal', 'sampai_tanggal'];

    protected function casts(): array
    {
        return [
            'dari_tanggal' => 'date',
            'sampai_tanggal' => 'date',
        ];
    }

    /** Anggota yang kartunya diperpanjang. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Petugas yang memperpanjang. */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
