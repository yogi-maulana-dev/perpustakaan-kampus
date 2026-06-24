<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    protected $fillable = ['judul', 'subjudul', 'gambar', 'gambar_mobile', 'urutan', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'urutan' => 'integer'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('aktif', true)->orderBy('urutan')->orderByDesc('id');
    }

    public function gambarUrl(): string
    {
        return Storage::disk('public')->url($this->gambar);
    }

    /** Gambar untuk tampilan HP; fallback ke gambar utama bila belum diisi. */
    public function gambarMobileUrl(): string
    {
        return Storage::disk('public')->url($this->gambar_mobile ?: $this->gambar);
    }
}
