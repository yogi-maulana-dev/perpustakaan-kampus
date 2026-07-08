<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case Librarian = 'Librarian';
    case Staff = 'Staff';
    case Anggota = 'Anggota';

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }

    /** Role yang punya akses panel staf/admin. */
    public static function staffRoles(): array
    {
        return [self::SuperAdmin->value, self::Admin->value, self::Librarian->value, self::Staff->value];
    }

    /** Role tingkat pengelola (Manajemen Staff, Pengaturan, Pengurus). */
    public static function managerRoles(): array
    {
        return [self::SuperAdmin->value, self::Admin->value];
    }
}
