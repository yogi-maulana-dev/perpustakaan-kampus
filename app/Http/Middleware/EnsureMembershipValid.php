<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMembershipValid
{
    /**
     * Anggota yang masa keanggotaannya sudah lewat diarahkan ke halaman
     * pemberitahuan kadaluarsa sampai kartunya diperpanjang oleh petugas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->hasRole(RoleName::Anggota->value)
            && $user->mahasiswaProfile?->kartuKadaluarsa()
            && ! $request->routeIs('membership.expired', 'logout')
        ) {
            return redirect()->route('membership.expired');
        }

        return $next($request);
    }
}
