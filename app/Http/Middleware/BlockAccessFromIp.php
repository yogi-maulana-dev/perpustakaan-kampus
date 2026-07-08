<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak seluruh request dari IP yang diblokir Super Admin.
 * Super Admin yang sudah login dikecualikan agar tetap bisa mengelola blokir.
 */
class BlockAccessFromIp
{
    public function handle(Request $request, Closure $next): Response
    {
        // Endpoint berbagi lokasi tetap boleh diakses agar pengguna yang diblokir
        // masih bisa mengirim lokasinya (sebagai bahan verifikasi/appeal).
        if ($request->routeIs('location.share')) {
            return $next($request);
        }

        if (BlockedIp::isBlocked($request->ip())) {
            $user = $request->user();

            if (! ($user && $user->hasRole('Super Admin'))) {
                return response()->view('errors.blocked', [], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
