<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'loan_id',
        'returned_by',
        'tanggal_kembali',
        'kondisi',
        'catatan',
    ];

    protected function casts(): array
    {
        return ['tanggal_kembali' => 'date'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
