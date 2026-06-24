<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberCardController extends Controller
{
    /**
     * Kartu anggota tunggal (petugas atau pemilik akun).
     */
    public function show(User $user): View
    {
        abort_unless(
            auth()->id() === $user->id || auth()->user()->can('approve mahasiswa'),
            403
        );

        $user->loadMissing('mahasiswaProfile');
        abort_unless($user->mahasiswaProfile, 404);

        return view('kartu-anggota', [
            'user' => $user,
            'profile' => $user->mahasiswaProfile,
        ]);
    }

    /**
     * Cetak massal kartu anggota berdasarkan daftar id terpilih.
     */
    public function bulk(Request $request): View
    {
        abort_unless(auth()->user()->can('approve mahasiswa'), 403);

        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));

        $users = User::with('mahasiswaProfile')
            ->whereIn('id', $ids)
            ->whereHas('mahasiswaProfile')
            ->orderBy('name')
            ->get();

        abort_if($users->isEmpty(), 404);

        return view('kartu-anggota-massal', ['users' => $users]);
    }
}
