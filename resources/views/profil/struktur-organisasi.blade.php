<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struktur Organisasi — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{background:#f0fdf4}</style>
</head>
<body class="min-h-screen font-sans text-gray-800 antialiased">
    @include('partials.public-nav', ['solid' => true])

    @php
        $strukturUrl = \App\Models\Setting::strukturUrl();
        $ket = \App\Models\Setting::get('struktur_keterangan');
    @endphp

    <main class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        <div class="text-center">
            <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Profil</span>
            <h1 class="mt-3 text-3xl font-extrabold text-emerald-900 sm:text-4xl">Struktur Organisasi</h1>
        </div>

        @if ($strukturUrl)
            <div class="mt-10 rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:p-6">
                <a href="{{ $strukturUrl }}" target="_blank" rel="noopener" title="Klik untuk memperbesar">
                    <img src="{{ $strukturUrl }}" alt="Struktur Organisasi Perpustakaan" class="mx-auto w-full max-w-4xl rounded-lg object-contain">
                </a>
            </div>
            @if ($ket)
                <p class="mt-4 text-center text-sm text-gray-500">{{ $ket }}</p>
            @endif
        @else
            <p class="mt-10 rounded-2xl border border-dashed border-emerald-200 bg-white p-10 text-center text-gray-400">Gambar struktur organisasi belum diunggah admin.</p>
        @endif

        <div class="mt-10 text-center">
            <a href="{{ route('home') }}" class="text-sm font-semibold text-emerald-700 hover:underline">&larr; Kembali ke Beranda</a>
        </div>
    </main>

    <footer class="border-t border-emerald-100 bg-white py-6 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} Perpustakaan Universitas Muhammadiyah Lampung
    </footer>
</body>
</html>
