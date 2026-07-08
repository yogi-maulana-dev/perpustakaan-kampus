<?php

namespace App\Http\Controllers;

use App\Models\LocationPing;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Terima koordinat lokasi (setelah pengguna mengizinkan) lalu simpan.
     * Dipanggil dari halaman login (saat IP mencurigakan) atau halaman "Akses Dibatasi".
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $ping = LocationPing::create([
            'ip_address' => $request->ip(),
            'email' => $data['email'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => isset($data['accuracy']) ? (int) $data['accuracy'] : null,
        ]);

        ActivityLogger::log(
            'lokasi_dibagikan',
            'Lokasi dibagikan (±'.($ping->accuracy ?? '?').' m): '.$ping->mapsUrl(),
            email: $data['email'] ?? null,
        );

        return response()->json(['ok' => true]);
    }
}
