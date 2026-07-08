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
        'kartu_berlaku_sampai',
    ];

    protected function casts(): array
    {
        return [
            'tipe' => MemberType::class,
            'kartu_berlaku_sampai' => 'date',
        ];
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

    /**
     * Tanggal berakhir keanggotaan/kartu. Bila belum pernah diperpanjang,
     * dihitung dari tanggal daftar + masa berlaku (Pengaturan, default 5 tahun).
     */
    public function kartuBerlakuSampai(): ?\Illuminate\Support\Carbon
    {
        if ($this->kartu_berlaku_sampai) {
            return \Illuminate\Support\Carbon::parse($this->kartu_berlaku_sampai);
        }

        $tahun = max(1, (int) Setting::get('masa_berlaku_kartu', 5));

        return $this->user?->created_at?->copy()->addYears($tahun);
    }

    /** Keanggotaan sudah lewat masa berlaku? */
    public function kartuKadaluarsa(): bool
    {
        $sampai = $this->kartuBerlakuSampai();

        return $sampai !== null && $sampai->copy()->endOfDay()->isPast();
    }
}
