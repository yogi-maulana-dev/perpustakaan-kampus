<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /** Lihat detail peminjaman: pemilik atau petugas. */
    public function view(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id || $user->canany(['kelola peminjaman', 'input peminjaman']);
    }

    /** Setujui/tolak peminjaman. */
    public function approve(User $user, Loan $loan): bool
    {
        return $user->can('kelola peminjaman');
    }

    /** Proses pengembalian. */
    public function processReturn(User $user, Loan $loan): bool
    {
        return $user->can('kelola pengembalian');
    }

    /** Perpanjang masa pinjam: pemilik atau petugas peminjaman. */
    public function renew(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id || $user->can('kelola peminjaman');
    }

    /** Tandai hilang: petugas pengembalian. */
    public function markLost(User $user, Loan $loan): bool
    {
        return $user->can('kelola pengembalian');
    }
}
