<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberHasPhoto
{
    /**
     * Anggota wajib mengunggah foto untuk kartu sebelum dapat melanjutkan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->hasRole(RoleName::Anggota->value)
            && ! optional($user->mahasiswaProfile)->foto
            && ! $request->routeIs('member.photo', 'logout')
        ) {
            return redirect()->route('member.photo');
        }

        return $next($request);
    }
}
