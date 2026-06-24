<?php

namespace App\Models;

use App\Enums\MemberType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MahasiswaProfile extends Model
{
    protected $fillable = [
        'user_id',
        'tipe',
        'nim',
        'nidn',
        'nbm',
        'nomor_identitas',
        'fakultas',
        'program_studi',
        'kode_prodi',
        'jenjang',
        'angkatan',
        'pekerjaan',
        'instansi',
        'no_hp',
        'ktm_path',
        'foto',
    ];

    protected function casts(): array
    {
        return ['tipe' => MemberType::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Nomor identitas utama sesuai tipe anggota. */
    public function nomorIdentitas(): ?string
    {
        return match ($this->tipe) {
            MemberType::Dosen => $this->nidn ?: $this->nbm,
            MemberType::Umum => $this->nomor_identitas,
            default => $this->nim,
        };
    }
}
