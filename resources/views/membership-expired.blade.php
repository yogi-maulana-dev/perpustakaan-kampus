<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Keanggotaan Kadaluarsa — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{background:#f0fdf4}</style>
</head>
<body class="grid min-h-screen place-items-center p-4 font-sans text-gray-800 antialiased">
    @php
        $sampai = $profile->kartuBerlakuSampai();
        $waNumber = \App\Models\Setting::waPerpanjanganNumber();
        $pesan = 'Halo, saya '.auth()->user()->name.' (email: '.auth()->user()->email.') ingin memperpanjang keanggotaan perpustakaan yang sudah kadaluarsa'
            .($sampai ? ' pada '.$sampai->format('d-m-Y') : '').'. Terima kasih.';
        $waUrl = $waNumber ? 'https://wa.me/'.$waNumber.'?text='.rawurlencode($pesan) : null;
    @endphp

    <div class="w-full max-w-md rounded-2xl border border-emerald-100 bg-white p-8 text-center shadow-xl">
        <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo" class="mx-auto h-12 w-auto object-contain">

        <div class="mx-auto mt-5 grid h-14 w-14 place-items-center rounded-full bg-amber-100 text-amber-600">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </div>

        <h1 class="mt-4 text-xl font-extrabold text-emerald-950">Keanggotaan Sudah Kadaluarsa</h1>
        <p class="mt-2 text-sm leading-relaxed text-gray-600">
            Masa berlaku keanggotaan Anda berakhir pada
            <strong>{{ $sampai?->locale('id')->translatedFormat('d F Y') ?? '-' }}</strong>.
            Silakan perpanjang dengan <strong>menghubungi staff perpustakaan</strong> agar akun Anda aktif kembali.
        </p>

        <div class="mt-6 flex flex-col gap-2">
            @if ($waUrl)
                <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm5.2 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .6l-.4.6-.3.4c-.1.2-.2.3 0 .6.2.3.9 1.4 1.9 2.3 1.3 1.2 2.4 1.5 2.7 1.7.3.1.5.1.7-.1l1-1.2c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.4 0 .1 0 .8-.2 1.4z"/></svg>
                    Hubungi Staff via WhatsApp
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-lg border px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Keluar</button>
            </form>
        </div>

        <p class="mt-5 text-xs text-gray-400">Setelah diperpanjang oleh petugas, silakan login kembali seperti biasa.</p>
    </div>
</body>
</html>
