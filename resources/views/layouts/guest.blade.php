<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col items-center bg-gradient-to-b from-emerald-950 to-emerald-800 pt-8 sm:justify-center sm:pt-0">
            <a href="/" wire:navigate class="flex items-center rounded-xl bg-white px-5 py-3 shadow">
                <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo Universitas Muhammadiyah Lampung" class="h-12 w-auto object-contain">
            </a>
            <p class="mt-3 text-sm font-medium text-emerald-100">Perpustakaan — Universitas Muhammadiyah Lampung</p>

            <div class="mt-6 w-full overflow-hidden bg-white px-6 py-6 shadow-xl ring-1 ring-black/5 sm:max-w-md sm:rounded-2xl">
                {{ $slot }}
            </div>

            <a href="{{ route('home') }}" class="mt-5 inline-flex items-center gap-1 text-sm text-emerald-100 hover:text-white">
                &larr; Kembali ke Beranda
            </a>
        </div>
    </body>
</html>
