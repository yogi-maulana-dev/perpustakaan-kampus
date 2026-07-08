<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Book extends Model
{
    protected $fillable = [
        'uuid',
        'kode_buku',
        'isbn',
        'judul',
        'category_id',
        'author_id',
        'publisher_id',
        'shelf_id',
        'tahun_terbit',
        'cetakan',
        'jumlah_stok',
        'stok_tersedia',
        'deskripsi',
        'cover',
    ];

    protected static function booted(): void
    {
        // UUID otomatis untuk URL publik.
        static::creating(function (self $book): void {
            $book->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'tahun_terbit' => 'integer',
            'jumlah_stok' => 'integer',
            'stok_tersedia' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function loanDetails(): HasMany
    {
        return $this->hasMany(LoanDetail::class);
    }

    public function isAvailable(): bool
    {
        return $this->stok_tersedia > 0;
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->cover
            ? Storage::disk('public')->url($this->cover)
            : asset('images/no-cover.svg'));
    }
}
