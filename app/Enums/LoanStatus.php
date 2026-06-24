<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Pending = 'pending';
    case Dipinjam = 'dipinjam';
    case Dikembalikan = 'dikembalikan';
    case Terlambat = 'terlambat';
    case Ditolak = 'ditolak';
    case Hilang = 'hilang';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Dipinjam => 'Sedang Dipinjam',
            self::Dikembalikan => 'Dikembalikan',
            self::Terlambat => 'Terlambat',
            self::Ditolak => 'Ditolak',
            self::Hilang => 'Hilang',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Dipinjam => 'indigo',
            self::Dikembalikan => 'emerald',
            self::Terlambat => 'rose',
            self::Ditolak => 'zinc',
            self::Hilang => 'rose',
        };
    }

    /** Status yang dianggap "aktif" (buku masih di tangan peminjam). */
    public static function active(): array
    {
        return [self::Dipinjam, self::Terlambat];
    }
}
