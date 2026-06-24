<?php

namespace App\Enums;

enum FineStatus: string
{
    case BelumBayar = 'belum_bayar';
    case Lunas = 'lunas';
    case Dibebaskan = 'dibebaskan';

    public function label(): string
    {
        return match ($this) {
            self::BelumBayar => 'Belum Bayar',
            self::Lunas => 'Lunas',
            self::Dibebaskan => 'Dibebaskan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BelumBayar => 'rose',
            self::Lunas => 'emerald',
            self::Dibebaskan => 'zinc',
        };
    }
}
