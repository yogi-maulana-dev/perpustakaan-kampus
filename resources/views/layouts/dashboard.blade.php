<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-800">
<div class="min-h-screen lg:flex">

    {{-- Overlay (mobile) --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-emerald-950 text-emerald-100 transition-transform duration-200 lg:static lg:translate-x-0">
        <div class="border-b border-white/10 p-3">
            <div class="rounded-lg bg-white p-3">
                <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo Universitas Muhammadiyah Lampung" class="mx-auto h-auto w-full object-contain">
            </div>
            <p class="mt-2 text-center text-xs font-medium text-emerald-100">Perpustakaan Kampus</p>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
            <x-nav-link-side :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-nav-link-side>

            @role('Anggota')
                <x-nav-section>Menu Anggota</x-nav-section>
                <x-nav-link-side :href="route('catalog')" :active="request()->routeIs('catalog')" icon="book">Katalog Buku</x-nav-link-side>
                <x-nav-link-side :href="route('my-loans')" :active="request()->routeIs('my-loans')" icon="clock">Pinjaman Saya</x-nav-link-side>
                <x-nav-link-side :href="route('my-fines')" :active="request()->routeIs('my-fines')" icon="cash">Denda Saya</x-nav-link-side>
            @endrole

            @hasanyrole('Super Admin|Librarian|Staff')
                <x-nav-section>Operasional</x-nav-section>
                @can('approve mahasiswa')
                    <x-nav-link-side :href="route('students.index')" :active="request()->routeIs('students.*')" icon="user-check">Data Anggota</x-nav-link-side>
                @endcan
                @can('lihat buku')
                    <x-nav-link-side :href="route('books.index')" :active="request()->routeIs('books.*')" icon="book">Data Buku</x-nav-link-side>
                @endcan
                @can('kelola peminjaman')
                    <x-nav-link-side :href="route('loans.index')" :active="request()->routeIs('loans.*')" icon="swap">Peminjaman</x-nav-link-side>
                @elsecan('input peminjaman')
                    <x-nav-link-side :href="route('loans.index')" :active="request()->routeIs('loans.*')" icon="swap">Peminjaman</x-nav-link-side>
                @endcan
                @can('kelola pengembalian')
                    <x-nav-link-side :href="route('returns.index')" :active="request()->routeIs('returns.*')" icon="undo">Pengembalian</x-nav-link-side>
                @endcan
                @can('kelola denda')
                    <x-nav-link-side :href="route('fines.index')" :active="request()->routeIs('fines.*')" icon="cash">Denda</x-nav-link-side>
                @endcan

                @can('kelola master')
                    <x-nav-section>Master Data</x-nav-section>
                    <x-nav-link-side :href="route('categories.index')" :active="request()->routeIs('categories.*')" icon="tag">Kategori</x-nav-link-side>
                    <x-nav-link-side :href="route('authors.index')" :active="request()->routeIs('authors.*')" icon="pen">Penulis</x-nav-link-side>
                    <x-nav-link-side :href="route('publishers.index')" :active="request()->routeIs('publishers.*')" icon="building">Penerbit</x-nav-link-side>
                    <x-nav-link-side :href="route('shelves.index')" :active="request()->routeIs('shelves.*')" icon="grid">Rak Buku</x-nav-link-side>
                    <x-nav-link-side :href="route('sliders.index')" :active="request()->routeIs('sliders.*')" icon="image">Slider</x-nav-link-side>
                    <x-nav-link-side :href="route('pengurus.index')" :active="request()->routeIs('pengurus.*')" icon="users">Pengurus</x-nav-link-side>
                    <x-nav-link-side :href="route('ekatalog.index')" :active="request()->routeIs('ekatalog.*')" icon="book">E-Katalog</x-nav-link-side>
                @endcan

                @can('lihat laporan')
                    <x-nav-section>Laporan</x-nav-section>
                    <x-nav-link-side :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="chart">Laporan</x-nav-link-side>
                @endcan
            @endhasanyrole

            @hasrole('Super Admin')
                <x-nav-section>Administrasi</x-nav-section>
                <x-nav-link-side :href="route('users.index')" :active="request()->routeIs('users.*')" icon="users">Manajemen Staff</x-nav-link-side>
                <x-nav-link-side :href="route('settings.index')" :active="request()->routeIs('settings.*')" icon="cog">Pengaturan</x-nav-link-side>
            @endhasrole
        </nav>

        <div class="border-t border-white/10 p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-emerald-100 hover:bg-white/10 hover:text-white">
                    <x-icon name="logout" class="h-5 w-5" /> Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b bg-white px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden">
                    <x-icon name="menu" class="h-6 w-6" />
                </button>
                <h1 class="text-lg font-semibold text-gray-800">{{ $title ?? 'Dashboard' }}</h1>
            </div>

            <div class="flex items-center gap-3">
                {{-- Pendaftar anggota baru (real-time) --}}
                @can('approve mahasiswa')
                    <livewire:shared.new-members />
                @endcan

                {{-- Notifications --}}
                <livewire:shared.notifications-dropdown />

                {{-- User menu --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-100">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-600 text-sm font-semibold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden text-sm font-medium text-gray-700 sm:block">{{ auth()->user()->name }}</span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 mt-2 w-48 rounded-lg border bg-white py-1 shadow-lg">
                        <p class="px-4 py-2 text-xs text-gray-400">{{ auth()->user()->getRoleNames()->first() }}</p>
                        <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>
</div>

{{-- Toast --}}
<x-toast />
</body>
</html>
