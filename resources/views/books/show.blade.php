<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $book->judul }} — {{ config('app.name') }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($book->deskripsi), 150) }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        /* Transisi halus antar halaman (ala SPA) di browser modern */
        @view-transition { navigation: auto; }
    </style>
</head>
<body class="min-h-screen bg-emerald-50/60 font-sans text-gray-800 antialiased">

    {{-- Navbar (komponen bersama — sama dengan halaman depan) --}}
    @include('partials.public-nav', ['solid' => true])

    <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <nav class="mb-4 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="hover:text-emerald-700">Beranda</a>
            <span class="mx-1">/</span>
            <a href="{{ route('home') }}#koleksi" class="hover:text-emerald-700">Koleksi</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ \Illuminate\Support\Str::limit($book->judul, 40) }}</span>
        </nav>

        {{-- Kartu utama: cover kecil + seluruh detail dalam satu layar --}}
        <div class="rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-6 sm:flex-row">
                {{-- Cover (ukuran tetap, tidak pernah melebar) --}}
                <div class="mx-auto w-36 shrink-0 sm:mx-0 sm:w-40 lg:w-48">
                    <div class="group relative cursor-zoom-in overflow-hidden rounded-xl border bg-emerald-50 shadow-sm"
                         onclick="const z = document.getElementById('coverZoom'); z.classList.remove('hidden'); z.classList.add('flex'); document.body.style.overflow = 'hidden';"
                         title="Klik untuk memperbesar">
                        <div class="aspect-[3/4]">
                            <img src="{{ $book->cover_url }}" alt="{{ $book->judul }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                 onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'grid h-full place-items-center text-xs text-emerald-300',innerText:'Tanpa sampul'}))">
                        </div>
                        {{-- Ikon kaca pembesar saat hover --}}
                        <span class="pointer-events-none absolute bottom-2 right-2 grid h-7 w-7 place-items-center rounded-full bg-black/50 text-white opacity-0 transition group-hover:opacity-100">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4M11 8v6M8 11h6"/></svg>
                        </span>
                    </div>
                </div>

                {{-- Detail --}}
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($book->category)
                            <span class="rounded-full bg-yellow-300 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-900">{{ $book->category->nama }}</span>
                        @endif
                        @if ($book->stok_tersedia > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tersedia · {{ $book->stok_tersedia }}/{{ $book->jumlah_stok }} eksemplar
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 ring-1 ring-rose-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Sedang dipinjam semua
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-2.5 text-xl font-bold leading-snug text-emerald-950 sm:text-2xl">{{ $book->judul }}</h1>
                    <p class="mt-1 text-sm text-gray-500">oleh <span class="font-medium text-gray-700">{{ $book->author?->nama ?? 'Tanpa Nama Pengarang' }}</span></p>

                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-3 border-b border-dashed py-1.5"><dt class="text-gray-400">Kode Buku</dt><dd class="font-mono font-medium text-gray-800">{{ $book->kode_buku }}</dd></div>
                        <div class="flex justify-between gap-3 border-b border-dashed py-1.5"><dt class="text-gray-400">ISBN</dt><dd class="truncate font-medium text-gray-800">{{ $book->isbn ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3 border-b border-dashed py-1.5"><dt class="text-gray-400">Penerbit</dt><dd class="truncate font-medium text-gray-800">{{ $book->publisher?->nama ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3 border-b border-dashed py-1.5"><dt class="text-gray-400">Tahun Terbit</dt><dd class="font-medium text-gray-800">{{ $book->tahun_terbit ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3 border-b border-dashed py-1.5"><dt class="text-gray-400">Cetakan/Edisi</dt><dd class="font-medium text-gray-800">{{ $book->cetakan ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3 border-b border-dashed py-1.5"><dt class="text-gray-400">Lokasi Rak</dt><dd class="font-medium text-gray-800">{{ $book->shelf?->kode_rak ?? '—' }}</dd></div>
                    </dl>

                    {{-- Aksi --}}
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('register') }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">
                            Daftar untuk Meminjam
                        </a>
                        <a href="{{ route('login') }}" class="rounded-lg border border-emerald-300 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                            Sudah Anggota? Masuk
                        </a>
                        @if (\App\Models\Setting::waNumber())
                            @php
                                $waUrl = \App\Models\Setting::waUrl([
                                    'nama' => 'Calon Anggota',
                                    'identitas' => '-',
                                    'judul' => $book->judul,
                                    'kode' => $book->kode_buku,
                                ]);
                            @endphp
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-green-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2z"/></svg>
                                Tanya via WA
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if ($book->deskripsi)
                <div class="mt-6 border-t pt-4">
                    <h2 class="text-base font-semibold text-emerald-900">Deskripsi</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ $book->deskripsi }}</p>
                </div>
            @endif
        </div>

        {{-- Buku terkait --}}
        @if ($related->isNotEmpty())
            <section class="mt-10">
                <h2 class="mb-4 text-lg font-bold text-emerald-900">Buku Lain di Kategori {{ $book->category?->nama }}</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($related as $r)
                        <a href="{{ route('books.public', $r) }}" class="group overflow-hidden rounded-xl border bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            <div class="aspect-[3/4] bg-emerald-50">
                                <img src="{{ $r->cover_url }}" alt="{{ $r->judul }}" class="h-full w-full object-cover">
                            </div>
                            <div class="p-2.5">
                                <h3 class="line-clamp-2 text-xs font-semibold text-gray-800 group-hover:text-emerald-700">{{ $r->judul }}</h3>
                                <p class="mt-0.5 truncate text-[11px] text-gray-500">{{ $r->author?->nama }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('home') }}#koleksi" class="text-sm font-semibold text-emerald-700 hover:underline">&larr; Kembali ke koleksi</a>
        </div>
    </main>

    {{-- Lightbox zoom cover (klik gambar / Esc untuk menutup) --}}
    <div id="coverZoom" class="fixed inset-0 z-50 hidden cursor-zoom-out items-center justify-center bg-black/85 p-4 sm:p-10"
         onclick="this.classList.add('hidden'); this.classList.remove('flex'); document.body.style.overflow = '';">
        <img src="{{ $book->cover_url }}" alt="{{ $book->judul }}" class="max-h-full max-w-full rounded-xl shadow-2xl">
        <button type="button" aria-label="Tutup"
                class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-white/15 text-white hover:bg-white/30">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <script>
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const z = document.getElementById('coverZoom');
                z.classList.add('hidden'); z.classList.remove('flex');
                document.body.style.overflow = '';
            }
        });
    </script>

    <script>
        // Prefetch halaman saat link disorot/disentuh — pindah halaman terasa instan (ala SPA).
        (function () {
            const done = new Set();
            const prefetch = (e) => {
                const a = e.target.closest && e.target.closest('a[href]');
                if (!a || !a.href.startsWith(location.origin) || a.target === '_blank' || done.has(a.href)) return;
                done.add(a.href);
                const l = document.createElement('link');
                l.rel = 'prefetch'; l.href = a.href;
                document.head.appendChild(l);
            };
            document.addEventListener('mouseover', prefetch, { passive: true });
            document.addEventListener('touchstart', prefetch, { passive: true });
        })();
    </script>

    <footer class="mt-12 border-t bg-white py-6 text-center text-xs text-gray-400">
        © {{ date('Y') }} Perpustakaan Universitas Muhammadiyah Lampung · Dikembangkan oleh
        <a href="https://github.com/yogi-maulana-dev" target="_blank" rel="noopener" class="font-medium text-emerald-600 hover:underline">yogi-maulana-dev</a>
    </footer>
</body>
</html>
