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
        @keyframes floaty{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-16px) rotate(6deg)}}
    </style>
</head>
<body class="font-sans text-gray-800 antialiased">

    {{-- ===== Navbar ===== --}}
    <header class="absolute inset-x-0 top-0 z-40" id="nav">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-20 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 sm:gap-3">
                <span class="flex h-10 items-center rounded-xl bg-white/80 px-2 shadow-sm sm:h-12">
                    <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo UML" class="h-7 w-auto object-contain sm:h-9">
                </span>
                <span class="hidden leading-tight sm:block">
                    <span class="block text-base font-extrabold text-emerald-900">PERPUSTAKAAN</span>
                    <span class="block text-[11px] font-semibold tracking-wide text-emerald-600">UNIVERSITAS MUHAMMADIYAH LAMPUNG</span>
                </span>
            </a>
            <nav class="hidden items-center gap-7 text-sm font-semibold text-emerald-900 lg:flex">
                <a href="#beranda" class="text-emerald-700">Beranda</a>
                <a href="#tentang" class="hover:text-emerald-600">Tentang Kami</a>
                <a href="#koleksi" class="hover:text-emerald-600">Koleksi</a>
                <a href="#ekatalog" class="hover:text-emerald-600">E-Katalog</a>
                <a href="#fitur" class="hover:text-emerald-600">Layanan</a>
                <a href="#kontak" class="hover:text-emerald-600">Kontak</a>
            </nav>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('login') }}" class="grid h-9 w-9 place-items-center rounded-full bg-white text-emerald-700 shadow-sm hover:bg-emerald-50 sm:h-11 sm:w-11">
                    <x-icon name="search" class="h-5 w-5" />
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-700 px-3 py-2 text-xs font-semibold text-white shadow-lg hover:bg-emerald-800 sm:gap-2 sm:px-5 sm:py-3 sm:text-sm">
                    <x-icon name="user-check" class="h-4 w-4" /> Login<span class="hidden sm:inline">&nbsp;Mahasiswa</span>
                </a>
            </div>
        </div>
    </header>

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
                                        <p class="text-sm font-bold text-yellow-300">Rektor Universitas Muhammadiyah Lampung</p>
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

    {{-- ===== Pengurus Perpustakaan (slider) ===== --}}
    @if ($pengurus->isNotEmpty())
        <section id="fitur" class="px-4 py-16 sm:px-6">
            <div class="reveal mx-auto mb-8 max-w-6xl text-center">
                <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">Tim Kami</span>
                <h2 class="mt-3 text-2xl font-bold text-emerald-900 sm:text-3xl">Pengurus Perpustakaan</h2>
                <p class="mt-2 text-gray-500">Tim yang siap melayani kebutuhan literasi Anda.</p>
            </div>
            <div class="reveal mx-auto max-w-6xl">
                <div class="swiper pengurusSwiper pb-12">
                    <div class="swiper-wrapper">
                        @foreach ($pengurus as $p)
                            <div class="swiper-slide">
                                <div class="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                                    <div class="aspect-[4/5] bg-emerald-50">
                                        @if ($p->fotoUrl())
                                            <img src="{{ $p->fotoUrl() }}" alt="{{ $p->nama }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="grid h-full place-items-center text-5xl font-bold text-emerald-300">{{ strtoupper(substr($p->nama, 0, 1)) }}</div>
                                        @endif
                                    </div>
                                    <div class="p-4 text-center">
                                        <p class="font-bold text-emerald-950">{{ $p->nama }}</p>
                                        <p class="mt-0.5 text-sm font-medium text-emerald-700">{{ $p->jabatan }}</p>
                                    </div>
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
                    <div class="reveal group overflow-hidden rounded-xl border bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="aspect-[3/4] bg-emerald-50">
                            <img src="{{ $book->cover_url }}" alt="{{ $book->judul }}" class="h-full w-full object-cover">
                        </div>
                        <div class="p-3">
                            <h3 class="line-clamp-2 text-sm font-semibold text-gray-800">{{ $book->judul }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $book->author?->nama }}</p>
                        </div>
                    </div>
                @empty
                    <p class="col-span-full py-10 text-center text-gray-400">Belum ada koleksi.</p>
                @endforelse
            </div>
            <div class="reveal mt-8 text-center">
                <a href="{{ route('register') }}" class="inline-block rounded-full bg-emerald-700 px-7 py-3 text-sm font-semibold text-white hover:bg-emerald-800">Jadi anggota untuk meminjam &rarr;</a>
            </div>
        </div>
    </section>

    {{-- ===== E-Katalog ===== --}}
    @if ($ekatalog->isNotEmpty())
        <section id="ekatalog" class="bg-emerald-50/60 py-16">
            <div class="mx-auto max-w-7xl px-6">
                <div class="reveal mb-8 text-center">
                    <span class="rounded-full bg-yellow-300 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-900">E-Katalog</span>
                    <h2 class="mt-3 text-2xl font-bold text-emerald-900 sm:text-3xl">Katalog Digital</h2>
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
    @endphp
    <section id="kontak" class="bg-white py-16">
        <div class="reveal mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-2xl font-bold text-emerald-900 sm:text-3xl">Kontak Kami</h2>
            <p class="mx-auto mt-3 max-w-xl text-gray-600"><span class="font-semibold text-emerald-800">Assalamu'alaikum</span> 🙏 Ada pertanyaan? Hubungi kami via WhatsApp.</p>
            <div class="mx-auto mt-8 max-w-md rounded-2xl border border-emerald-100 bg-emerald-50 p-8 shadow-sm">
                <p class="text-sm text-gray-500">WhatsApp Perpustakaan</p>
                @if ($kontakWa)
                    <p class="mt-1 text-2xl font-bold tracking-wide text-emerald-900">+{{ $kontakWa }}</p>
                    <a href="https://wa.me/{{ $kontakWa }}?text={{ rawurlencode($kontakPesan) }}" target="_blank" rel="noopener"
                       class="mt-5 inline-flex items-center gap-2 rounded-full bg-green-500 px-6 py-3 text-sm font-semibold text-white hover:bg-green-600">Chat via WhatsApp</a>
                @else
                    <p class="mt-1 text-sm text-gray-400">Nomor WhatsApp belum diatur admin.</p>
                @endif
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
                    autoplay: { delay: 3000, disableOnInteraction: false },
                    breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 4 } },
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
