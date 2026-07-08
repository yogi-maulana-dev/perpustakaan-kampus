<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Universitas Muhammadiyah Lampung</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&family=pacifico" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@2.0.8/dist/lottie-player.js" defer></script>

    <style>
        body{background:#f0fdf4}
        .font-script{font-family:'Pacifico',cursive}
        /* Rektor cutout menyatu */
        .rektor-mask{
            -webkit-mask-image:linear-gradient(to bottom,#000 78%,transparent 99%),linear-gradient(to left,#000 82%,transparent 100%);
            -webkit-mask-composite:source-in;mask-composite:intersect;
            mask-image:linear-gradient(to bottom,#000 78%,transparent 99%),linear-gradient(to left,#000 82%,transparent 100%);
            filter:drop-shadow(0 24px 40px rgba(16,109,72,.25));
        }
        .heroSwiper .swiper-button-next,.heroSwiper .swiper-button-prev{
            width:46px;height:46px;border-radius:9999px;background:#fff;color:#059669;
            box-shadow:0 8px 22px rgba(5,150,105,.18)
        }
        .heroSwiper .swiper-button-next::after,.heroSwiper .swiper-button-prev::after{font-size:16px;font-weight:800}
        .heroSwiper .swiper-pagination-bullet{background:#cbd5e1;opacity:1}
        .heroSwiper .swiper-pagination-bullet-active{background:#059669;width:28px;border-radius:6px}
        .pengurusSwiper .swiper-pagination-bullet-active{background:#059669}
        /* Bingkai lengkung gradien hijau-kuning; BERPUTAR saat foto disentuh/hover (gaya Tim LP2M) */
        .tim-ring{position:relative}
        .tim-ring::before{content:"";position:absolute;inset:0;border-radius:9999px;
            background:conic-gradient(from 200deg,#15803d 0deg 132deg,transparent 132deg 176deg,#f5c518 176deg 320deg,transparent 320deg 360deg)}
        .tim-ring>*{position:relative;z-index:1}
        /* Muter hanya ketika disentuh / hover */
        .tim-ring:hover::before,.tim-ring:active::before,.swiper-slide:hover .tim-ring::before{animation:timspin 4s linear infinite}
        @keyframes timspin{to{transform:rotate(360deg)}}
        @media (prefers-reduced-motion:reduce){.tim-ring::before{animation:none}}
        @keyframes floaty{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-16px) rotate(6deg)}}
    </style>
</head>
<body class="font-sans text-gray-800 antialiased">

    {{-- ===== Navbar (komponen bersama — sama di semua halaman publik) ===== --}}
    @include('partials.public-nav')

    {{-- ===== HERO ===== --}}
    @php
        $heroSlides = $sliders->isNotEmpty()
            ? $sliders->map(fn ($s) => ['src' => $s->gambarUrl(), 'srcMobile' => $s->gambarMobileUrl(), 'title' => $s->judul, 'sub' => $s->subjudul])->all()
            : [
                ['src' => asset('images/slider/1.jpg'), 'srcMobile' => asset('images/slider/1.jpg'), 'title' => 'Perpustakaan Digital', 'sub' => 'Akses ribuan buku, jurnal, dan referensi akademik kapan saja dan di mana saja untuk mendukung pembelajaran tanpa batas.'],
                ['src' => asset('images/slider/2.jpg'), 'srcMobile' => asset('images/slider/2.jpg'), 'title' => 'Belajar Tanpa Batas', 'sub' => 'Koleksi e-book & jurnal lengkap untuk seluruh sivitas akademika.'],
                ['src' => asset('images/slider/3.jpg'), 'srcMobile' => asset('images/slider/3.jpg'), 'title' => 'Layanan Modern', 'sub' => 'Peminjaman online, perpanjangan otomatis, dan notifikasi jatuh tempo.'],
            ];
    @endphp

    <section id="beranda" class="relative overflow-hidden">
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                @foreach ($heroSlides as $i => $slide)
                    <div class="swiper-slide relative min-h-[560px] overflow-hidden md:min-h-[90vh]">
                        {{-- Background slider FULL di belakang semua --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-100 to-emerald-50"></div>
                        {{-- Desktop: gambar utama (utuh) --}}
                        <img src="{{ $slide['src'] }}" alt="" class="hero-bg absolute inset-0 hidden h-full w-full object-contain object-center md:block" onerror="this.remove()">
                        {{-- Mobile/iPhone SE: gambar potret khusus (mengisi penuh) --}}
                        <img src="{{ $slide['srcMobile'] }}" alt="" class="hero-bg absolute inset-0 h-full w-full object-cover object-top md:hidden" onerror="this.remove()">
                        {{-- overlay terang agar teks tetap terbaca + fade ke bawah --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-white/90 via-white/55 to-white/10"></div>
                        <div class="absolute inset-x-0 bottom-0 h-44 bg-gradient-to-t from-emerald-50 to-transparent"></div>

                        {{-- Daun melayang --}}
                        <svg class="pointer-events-none absolute -left-6 top-28 z-10 h-28 w-28 text-emerald-400/60" style="animation:floaty 7s ease-in-out infinite" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16 5 21c4-3 9-4 12-9 .6-1 1-2 1-3-2 0-3 .2-4 1z"/><path d="M5 21c0-7 5-11 13-12-2 6-7 11-13 12z" opacity=".5"/></svg>

                        <div class="relative z-10 mx-auto grid max-w-7xl grid-cols-1 items-center gap-4 px-6 pb-12 pt-24 md:min-h-[90vh] md:grid-cols-2 md:gap-6 md:pb-32 md:pt-28">
                            {{-- Kiri: teks (langsung di atas background, tanpa kotak) --}}
                            <div class="max-w-xl">
                                <span data-swiper-parallax="-80" class="inline-flex items-center gap-2 rounded-full bg-yellow-300 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-900 shadow-sm">
                                    <x-icon name="book" class="h-4 w-4" /> Welcome To
                                </span>

                                <div data-swiper-parallax="-300" class="mt-6 flex flex-wrap items-center gap-3 sm:mt-8 sm:gap-4">
                                    <a href="#koleksi" class="group inline-flex items-center gap-2 rounded-full bg-emerald-700/90 px-5 py-3 text-sm font-bold text-white shadow-xl shadow-emerald-700/20 backdrop-blur transition hover:bg-emerald-800 sm:gap-3 sm:px-7 sm:py-4">
                                        Jelajahi Koleksi
                                        <span class="grid h-6 w-6 place-items-center rounded-full bg-white/25 transition group-hover:translate-x-1 sm:h-7 sm:w-7">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                        </span>
                                    </a>
                                    <a href="#koleksi" class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/50 px-5 py-3 text-sm font-bold text-emerald-800 backdrop-blur transition hover:bg-white/80 sm:px-7 sm:py-3.5">
                                        Cari Buku <x-icon name="search" class="h-4 w-4" />
                                    </a>
                                </div>

                            </div>

                            {{-- Kanan: rektor (di atas background full) --}}
                            <div class="relative mx-auto h-72 w-full max-w-sm sm:h-80 md:mx-0 md:h-full md:min-h-[540px] md:max-w-none">
                                {{-- glow lembut di belakang rektor --}}
                                <div class="absolute bottom-8 right-8 hidden h-[420px] w-[360px] rounded-full bg-yellow-300/25 blur-3xl md:block"></div>
                                <div class="absolute bottom-0 right-0 hidden h-[320px] w-[320px] rounded-full bg-emerald-400/15 blur-3xl md:block"></div>

                                {{-- ikon floating (sembunyi di HP biar rapi) --}}
                                <span class="absolute left-2 top-16 hidden h-14 w-14 place-items-center rounded-full bg-emerald-600 text-white shadow-lg md:grid" style="animation:floaty 5s ease-in-out infinite"><x-icon name="book" class="h-6 w-6" /></span>
                                <span class="absolute left-0 top-1/2 hidden h-14 w-14 place-items-center rounded-full bg-yellow-400 text-emerald-900 shadow-lg md:grid" style="animation:floaty 6s ease-in-out infinite .6s"><x-icon name="graduation" class="h-6 w-6" /></span>
                                <span class="absolute left-6 bottom-24 hidden h-12 w-12 place-items-center rounded-full bg-white text-emerald-600 shadow-lg md:grid" style="animation:floaty 7s ease-in-out infinite .3s"><x-icon name="doc" class="h-5 w-5" /></span>

                                {{-- foto rektor --}}
                                <img src="{{ \App\Models\Setting::rektorUrl() }}" alt="Rektor"
                                     class="rektor rektor-mask absolute bottom-0 right-0 h-full w-auto object-contain object-bottom will-change-transform"
                                     onerror="this.closest('.relative').querySelector('.rektor-fallback')?.classList.remove('hidden');this.remove()">
                                <div class="rektor-fallback hidden absolute inset-0 grid place-items-center text-center text-emerald-300">
                                    <div><x-icon name="user-check" class="mx-auto h-24 w-24" /><p class="mt-2 text-sm">Foto rektor (rektor.png)</p></div>
                                </div>

                                {{-- caption rektor --}}
                                <div class="absolute bottom-6 right-2">
                                    <div class="rounded-xl bg-blue-950/80 px-4 py-2.5 text-right shadow-lg ring-1 ring-white/10 backdrop-blur">
                                        @if (\App\Models\Setting::get('rektor_nama'))
                                            <p class="font-script text-xl text-white">{{ \App\Models\Setting::get('rektor_nama') }}</p>
                                        @endif
                                        <p class="text-sm font-bold text-yellow-300">Kepala Perpustakaan Universitas Muhammadiyah Lampung</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="swiper-button-prev !left-2"></div>
            <div class="swiper-button-next !right-2"></div>
            <div class="swiper-pagination !bottom-20"></div>
        </div>
    </section>

    {{-- ===== Kartu Statistik (melayang) ===== --}}
    <section class="relative z-20 -mt-16 px-4 sm:px-6">
        <div class="reveal mx-auto grid max-w-6xl grid-cols-2 gap-2 rounded-2xl border border-emerald-100 bg-white p-6 shadow-xl sm:gap-6 lg:grid-cols-4">
            @php
                $stats = [
                    ['icon' => 'book', 'color' => 'emerald', 'val' => $totalBuku, 'label' => 'Koleksi Buku'],
                    ['icon' => 'users', 'color' => 'yellow', 'val' => $totalAnggota, 'label' => 'Anggota Aktif'],
                    ['icon' => 'doc', 'color' => 'emerald', 'val' => $totalKategori, 'label' => 'Kategori'],
                    ['icon' => 'swap', 'color' => 'yellow', 'val' => $totalPinjam, 'label' => 'Transaksi'],
                ];
            @endphp
            @foreach ($stats as $st)
                <div class="flex items-center gap-4 px-2">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl {{ $st['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-600' : 'bg-emerald-100 text-emerald-600' }}">
                        <x-icon :name="$st['icon']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-2xl font-extrabold text-emerald-950">{{ number_format($st['val']) }}+</p>
                        <p class="text-sm text-gray-500">{{ $st['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== Pengurus Perpustakaan (slider berjalan — gaya Tim LP2M) ===== --}}
    @if ($pengurus->isNotEmpty())
        <section id="fitur" class="px-4 py-16 sm:px-6">
            <div class="reveal mx-auto mb-10 max-w-6xl text-center">
                <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Tim Kami</span>
                <h2 class="mt-3 text-2xl font-bold text-emerald-900 sm:text-3xl">Pengurus Perpustakaan</h2>
                <p class="mt-2 text-gray-500">Tim yang siap melayani kebutuhan literasi Anda.</p>
            </div>
            <div class="reveal mx-auto max-w-6xl">
                <div class="swiper pengurusSwiper pb-14">
                    <div class="swiper-wrapper">
                        @foreach ($pengurus as $p)
                            <div class="swiper-slide">
                                <div class="flex flex-col items-center px-2 py-4 text-center transition hover:-translate-y-1">
                                    {{-- Foto bundar berbingkai lengkung gradien hijau-kuning --}}
                                    <div class="tim-ring rounded-full p-[6px] shadow-sm">
                                        <div class="rounded-full bg-white p-[4px]">
                                            <div class="aspect-square w-40 overflow-hidden rounded-full bg-emerald-50 sm:w-44">
                                                @if ($p->fotoUrl())
                                                    <img src="{{ $p->fotoUrl() }}" alt="{{ $p->nama }}" class="h-full w-full object-cover">
                                                @else
                                                    <div class="grid h-full place-items-center text-5xl font-bold text-emerald-300">{{ strtoupper(substr($p->nama, 0, 1)) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-5 text-base font-bold text-emerald-950">{{ $p->nama }}</p>
                                    <p class="mt-1 text-sm text-gray-500">{{ $p->jabatan }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Koleksi ===== --}}
    <section id="koleksi" class="bg-white py-16">
        <div class="mx-auto max-w-7xl px-6">
            <div class="reveal mb-8 text-center">
                <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Koleksi</span>
                <h2 class="mt-3 text-2xl font-bold text-emerald-900 sm:text-3xl">Koleksi Terbaru</h2>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @forelse ($books as $book)
                    <a href="{{ route('books.public', $book) }}" class="reveal group block overflow-hidden rounded-xl border bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="aspect-[3/4] bg-emerald-50">
                            <img src="{{ $book->cover_url }}" alt="{{ $book->judul }}" class="h-full w-full object-cover">
                        </div>
                        <div class="p-3">
                            <h3 class="line-clamp-2 text-sm font-semibold text-gray-800 group-hover:text-emerald-700">{{ $book->judul }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $book->author?->nama }}</p>
                            <span class="mt-1 inline-block text-[11px] font-semibold text-emerald-600">Lihat detail &rarr;</span>
                        </div>
                    </a>
                @empty
                    <p class="col-span-full py-10 text-center text-gray-400">Belum ada koleksi.</p>
                @endforelse
            </div>
            <div class="reveal mt-8 text-center">
                <a href="{{ route('register') }}" class="inline-block rounded-full bg-emerald-700 px-7 py-3 text-sm font-semibold text-white hover:bg-emerald-800">Jadi anggota untuk meminjam &rarr;</a>
            </div>
        </div>
    </section>

    {{-- ===== E-Resources ===== --}}
    @if ($ekatalog->isNotEmpty())
        <section id="ekatalog" class="bg-emerald-50/60 py-16">
            <div class="mx-auto max-w-7xl px-6">
                <div class="reveal mb-8 text-center">
                    <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">E-Resources</span>
                    <h2 class="mt-3 text-2xl font-bold text-emerald-900 sm:text-3xl">E-Resources Perpustakaan</h2>
                    <p class="mt-2 text-gray-500">Koleksi digital & sumber referensi online perpustakaan.</p>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($ekatalog as $e)
                        <div class="reveal flex flex-col overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            <div class="aspect-[16/10] bg-emerald-50">
                                @if ($e->gambarUrl())
                                    <img src="{{ $e->gambarUrl() }}" alt="{{ $e->judul }}" class="h-full w-full object-cover">
                                @else
                                    <div class="grid h-full place-items-center text-emerald-200"><x-icon name="book" class="h-10 w-10" /></div>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col p-4">
                                <h3 class="font-bold text-emerald-950">{{ $e->judul }}</h3>
                                <p class="mt-1 line-clamp-3 text-sm text-gray-500">{{ $e->deskripsi }}</p>
                                @if ($e->link)
                                    <a href="{{ $e->link }}" target="_blank" rel="noopener" class="mt-3 inline-flex w-max items-center gap-1 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800">Buka Koleksi &rarr;</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Tentang + Lottie ===== --}}
    <section id="tentang" class="bg-emerald-50 py-16">
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-6 md:grid-cols-2">
            <div class="reveal">
                <lottie-player src="{{ asset('animations/reading.json') }}" background="transparent" speed="1" loop autoplay
                    style="width:100%;max-width:460px;height:340px;margin:auto"></lottie-player>
            </div>
            <div class="reveal">
                <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Tentang</span>
                <h2 class="mt-3 text-2xl font-bold text-emerald-900 sm:text-3xl">Pusat Informasi Akademik Modern</h2>
                <p class="mt-4 text-gray-600">Perpustakaan Universitas Muhammadiyah Lampung menyediakan layanan peminjaman koleksi
                    buku & referensi bagi mahasiswa, dosen, serta masyarakat umum, dengan pendaftaran mandiri yang diverifikasi pustakawan.</p>
                <ul class="mt-6 space-y-3 text-sm text-gray-700">
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-600 text-white">✓</span> Pendaftaran online Mahasiswa, Dosen &amp; Umum</li>
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-600 text-white">✓</span> Peminjaman, perpanjangan &amp; pengembalian digital</li>
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-600 text-white">✓</span> Notifikasi jatuh tempo &amp; denda otomatis</li>
                </ul>
                <a href="{{ route('register') }}" class="mt-6 inline-block rounded-full bg-emerald-700 px-7 py-3 text-sm font-semibold text-white hover:bg-emerald-800">Daftar Sekarang</a>
            </div>
        </div>
    </section>

    {{-- ===== Kontak ===== --}}
    @php
        $kontakWa = \App\Models\Setting::waNumber();
        $kontakPesan = 'Assalamu\'alaikum, saya ingin bertanya tentang keanggotaan & peminjaman di Perpustakaan UML. Terima kasih 🙏';
        $kAlamat = \App\Models\Setting::get('kontak_alamat');
        $kTelepon = \App\Models\Setting::get('kontak_telepon');
        $kEmail = \App\Models\Setting::get('kontak_email');
        $kJam = \App\Models\Setting::get('kontak_jam');
        $kIg = \App\Models\Setting::get('kontak_instagram');
        $kFb = \App\Models\Setting::get('kontak_facebook');
        $kMaps = \App\Models\Setting::get('kontak_maps');
        $kMapsEmbed = $kMaps && str_contains($kMaps, '/embed');
        $igUrl = $kIg ? (str_starts_with($kIg, 'http') ? $kIg : 'https://instagram.com/'.ltrim($kIg, '@')) : null;
    @endphp
    <section id="kontak" class="bg-white py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="reveal mb-10 text-center">
                <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Hubungi Kami</span>
                <h2 class="mt-3 text-2xl font-bold text-emerald-900 sm:text-3xl">Kontak Kami</h2>
                <p class="mx-auto mt-2 max-w-xl text-gray-500"><span class="font-semibold text-emerald-800">Assalamu'alaikum</span> 🙏 Ada pertanyaan? Silakan hubungi kami.</p>
            </div>

            <div class="reveal grid gap-6 lg:grid-cols-2">
                {{-- Info kontak --}}
                <div class="space-y-4">
                    @if ($kAlamat)
                        <div class="flex items-start gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="home" class="h-5 w-5" /></span>
                            <div><p class="text-sm font-semibold text-emerald-900">Alamat</p><p class="mt-0.5 text-sm text-gray-600">{{ $kAlamat }}</p></div>
                        </div>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2">
                        @if ($kTelepon)
                            <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="phone" class="h-5 w-5" /></span>
                                <div class="min-w-0"><p class="text-sm font-semibold text-emerald-900">Telepon</p><p class="mt-0.5 truncate text-sm text-gray-600">{{ $kTelepon }}</p></div>
                            </div>
                        @endif
                        @if ($kEmail)
                            <a href="mailto:{{ $kEmail }}" class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5 hover:border-emerald-300">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="bell" class="h-5 w-5" /></span>
                                <div class="min-w-0"><p class="text-sm font-semibold text-emerald-900">Email</p><p class="mt-0.5 truncate text-sm text-gray-600">{{ $kEmail }}</p></div>
                            </a>
                        @endif
                        @if ($kJam)
                            <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="clock" class="h-5 w-5" /></span>
                                <div class="min-w-0"><p class="text-sm font-semibold text-emerald-900">Jam Operasional</p><p class="mt-0.5 text-sm text-gray-600">{{ $kJam }}</p></div>
                            </div>
                        @endif
                        @if ($kontakWa)
                            <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
                                </span>
                                <div class="min-w-0"><p class="text-sm font-semibold text-emerald-900">WhatsApp</p><p class="mt-0.5 truncate text-sm text-gray-600">+{{ $kontakWa }}</p></div>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        @if ($kontakWa)
                            <a href="https://wa.me/{{ $kontakWa }}?text={{ rawurlencode($kontakPesan) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 rounded-full bg-green-500 px-6 py-3 text-sm font-semibold text-white hover:bg-green-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
                                Chat via WhatsApp
                            </a>
                        @endif
                        @if ($igUrl)
                            <a href="{{ $igUrl }}" target="_blank" rel="noopener" aria-label="Instagram"
                               class="grid h-11 w-11 place-items-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                            </a>
                        @endif
                        @if ($kFb)
                            <a href="{{ $kFb }}" target="_blank" rel="noopener" aria-label="Facebook"
                               class="grid h-11 w-11 place-items-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H7v3h3v6h3v-6h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>
                            </a>
                        @endif
                    </div>

                    @unless ($kAlamat || $kTelepon || $kEmail || $kJam || $kontakWa)
                        <p class="text-sm text-gray-400">Informasi kontak belum diatur admin.</p>
                    @endunless
                </div>

                {{-- Peta / lokasi --}}
                <div>
                    @if ($kMapsEmbed)
                        <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-emerald-100 shadow-sm lg:h-full">
                            <iframe src="{{ $kMaps }}" class="h-full w-full" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                        </div>
                    @elseif ($kMaps)
                        <a href="{{ $kMaps }}" target="_blank" rel="noopener"
                           class="grid aspect-[4/3] place-items-center rounded-2xl border border-emerald-100 bg-emerald-50 text-center shadow-sm hover:bg-emerald-100 lg:h-full">
                            <span>
                                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="home" class="h-6 w-6" /></span>
                                <span class="mt-3 block text-sm font-semibold text-emerald-800">Lihat Lokasi di Google Maps &rarr;</span>
                            </span>
                        </a>
                    @else
                        <div class="grid aspect-[4/3] place-items-center rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/50 text-sm text-emerald-400 lg:h-full">Lokasi belum diatur</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-emerald-950 py-8 text-center text-sm text-emerald-200">
        <p>Didukung oleh Tim IT dan Pegawai Perpustakaan Universitas Muhammadiyah Lampung</p>
        <p class="mt-1">Dikembangkan oleh
            <a href="https://github.com/yogi-maulana-dev" target="_blank" rel="noopener" class="font-semibold text-yellow-300 hover:underline">yogi-maulana-dev</a>
        </p>
        <p class="mt-2 text-xs text-emerald-400">&copy; {{ date('Y') }} Perpustakaan UML. Seluruh hak cipta dilindungi.</p>
    </footer>

    {{-- Butuh Bantuan? --}}
    @if ($kontakWa)
        <a href="https://wa.me/{{ $kontakWa }}?text={{ rawurlencode($kontakPesan) }}" target="_blank" rel="noopener"
           class="fixed bottom-5 right-5 z-40 flex items-center gap-3 rounded-full bg-emerald-700 py-2.5 pl-3 pr-5 text-white shadow-xl transition hover:bg-emerald-800">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-emerald-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
            </span>
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-bold leading-tight">Butuh Bantuan?</span>
                <span class="block text-xs text-emerald-100">Kami siap membantu Anda</span>
            </span>
        </a>
    @endif

    <script>
        window.addEventListener('load', function () {
            const swiper = new Swiper('.heroSwiper', {
                loop: true, speed: 1000, parallax: true,
                autoplay: { delay: 6000, disableOnInteraction: false },
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                pagination: { el: '.swiper-pagination', clickable: true },
            });

            // Slider Pengurus Perpustakaan
            if (document.querySelector('.pengurusSwiper')) {
                new Swiper('.pengurusSwiper', {
                    loop: {{ $pengurus->count() > 4 ? 'true' : 'false' }},
                    speed: 700,
                    spaceBetween: 20,
                    slidesPerView: 1.2,
                    autoplay: { delay: 2800, disableOnInteraction: false },
                    breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } },
                    pagination: { el: '.pengurusSwiper .swiper-pagination', clickable: true },
                });
            }

            function animateSlide() {
                if (!window.gsap) return;
                const active = document.querySelector('.swiper-slide-active');
                if (!active) return;
                gsap.fromTo(active.querySelectorAll('[data-swiper-parallax]'),
                    { y: 36, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: 'power3.out' });
                const r = active.querySelector('.rektor');
                if (r) {
                    gsap.killTweensOf(r);
                    // Animasi masuk smooth dari bawah-samping...
                    gsap.fromTo(r, { y: 80, x: 28, opacity: 0 }, {
                        y: 0, x: 0, opacity: 1, duration: 1.2, ease: 'power3.out',
                        onComplete: () => {
                            // ...lalu bergerak halus naik-turun terus (efek hidup)
                            gsap.to(r, { y: -16, duration: 3, repeat: -1, yoyo: true, ease: 'sine.inOut' });
                        }
                    });
                }
            }
            swiper.on('slideChangeTransitionStart', animateSlide);
            animateSlide();

            if (window.gsap && window.ScrollTrigger) {
                gsap.registerPlugin(ScrollTrigger);
                gsap.utils.toArray('.reveal').forEach((el) => {
                    gsap.fromTo(el, { y: 48, opacity: 0 },
                        { y: 0, opacity: 1, duration: 0.8, ease: 'power2.out', scrollTrigger: { trigger: el, start: 'top 88%' } });
                });
            }
        });
    </script>
</body>
</html>
