<?php

namespace App\Enums;

enum MemberType: string
{
    case Mahasiswa = 'mahasiswa';
    case Dosen = 'dosen';
    case Umum = 'umum';

    public function label(): string
    {
        return match ($this) {
            self::Mahasiswa => 'Mahasiswa',
            self::Dosen => 'Dosen',
            self::Umum => 'Umum',
        };
    }

    /** Label nomor identitas sesuai tipe. */
    public function idLabel(): string
    {
        return match ($this) {
            self::Mahasiswa => 'NIM',
            self::Dosen => 'NIDN',
            self::Umum => 'No. KTP',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
