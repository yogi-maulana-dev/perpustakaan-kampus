<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sejarah — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{background:#f0fdf4}</style>
</head>
<body class="min-h-screen font-sans text-gray-800 antialiased">
    @include('partials.public-nav', ['solid' => true])

    @php $sejarah = \App\Models\Setting::get('profil_sejarah'); @endphp

    <main class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <div class="text-center">
            <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Profil</span>
            <h1 class="mt-3 text-3xl font-extrabold text-emerald-900 sm:text-4xl">Sejarah</h1>
        </div>

        @if ($sejarah)
            <div class="prose-emerald mt-10 rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm sm:p-8">
                <div class="space-y-4 leading-relaxed text-gray-700">
                    @foreach (preg_split('/\n\s*\n/', $sejarah) as $par)
                        @if (trim($par) !== '')<p class="whitespace-pre-line">{{ trim($par) }}</p>@endif
                    @endforeach
                </div>
            </div>
        @else
            <p class="mt-10 rounded-2xl border border-dashed border-emerald-200 bg-white p-10 text-center text-gray-400">Konten Sejarah belum diisi admin.</p>
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
