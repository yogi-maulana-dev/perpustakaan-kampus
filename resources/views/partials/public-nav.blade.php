{{-- Navbar publik bersama (landing, detail koleksi, panduan). Menu desktop & HP sama persis.
     Pakai: @include('partials.public-nav') — opsional ['solid' => true] untuk header putih menempel atas. --}}
@php $solid = $solid ?? false; @endphp
<header id="nav" class="{{ $solid ? 'sticky top-0 z-40 border-b border-emerald-100 bg-white/95 backdrop-blur' : 'absolute inset-x-0 top-0 z-40' }}">
    <div class="mx-auto flex h-16 max-w-[90rem] items-center justify-between px-4 sm:h-20 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2 sm:gap-3">
            <span class="flex h-10 items-center rounded-xl bg-white/80 px-2 shadow-sm sm:h-12">
                <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo UML" class="h-7 w-auto object-contain sm:h-9">
            </span>
            <span class="hidden leading-tight sm:block lg:hidden min-[1400px]:block">
                <span class="block text-base font-extrabold text-emerald-900">PERPUSTAKAAN</span>
                <span class="block text-[11px] font-semibold tracking-wide text-emerald-600">UNIVERSITAS MUHAMMADIYAH LAMPUNG</span>
            </span>
        </a>

        <nav class="hidden items-center gap-4 whitespace-nowrap text-[13px] font-semibold text-emerald-900 lg:flex xl:gap-6 xl:text-sm">
            <a href="{{ route('home') }}#beranda" class="hover:text-emerald-600">Beranda</a>
            {{-- Profil dropdown (JS biasa, tanpa Alpine) --}}
            <div class="relative" data-profil-dropdown>
                <button type="button" data-profil-toggle class="inline-flex items-center gap-1 hover:text-emerald-600">
                    Profil
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div data-profil-menu class="absolute left-0 top-full z-50 hidden w-52 rounded-xl border border-emerald-100 bg-white py-2 shadow-xl">
                    <a href="{{ route('profil.visi-misi') }}" class="block px-4 py-2 text-sm font-medium text-emerald-900 hover:bg-emerald-50">Visi &amp; Misi</a>
                    <a href="{{ route('profil.sejarah') }}" class="block px-4 py-2 text-sm font-medium text-emerald-900 hover:bg-emerald-50">Sejarah</a>
                    <a href="{{ route('profil.struktur-organisasi') }}" class="block px-4 py-2 text-sm font-medium text-emerald-900 hover:bg-emerald-50">Struktur Organisasi</a>
                </div>
            </div>
            <a href="{{ route('home') }}#koleksi" class="hover:text-emerald-600">Koleksi</a>
            <a href="{{ route('home') }}#ekatalog" class="hover:text-emerald-600">E-Resources</a>
            <a href="{{ route('home') }}#fitur" class="hover:text-emerald-600">Layanan</a>
            <a href="{{ route('panduan.anggota') }}" class="hover:text-emerald-600">Panduan Anggota</a>
            <a href="{{ route('home') }}#kontak" class="hover:text-emerald-600">Kontak</a>
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-700 px-3 py-2 text-xs font-semibold text-white shadow-lg hover:bg-emerald-800 sm:gap-2 sm:px-5 sm:py-3 sm:text-sm">
                <x-icon name="user-check" class="h-4 w-4" /> Login<span class="hidden sm:inline">&nbsp;Mahasiswa</span>
            </a>
            {{-- Tombol menu (HP/tablet) --}}
            <button type="button" aria-label="Buka menu" class="grid h-9 w-9 place-items-center rounded-full bg-white text-emerald-700 shadow-sm hover:bg-emerald-50 lg:hidden sm:h-11 sm:w-11"
                    onclick="document.getElementById('mobileNav').classList.toggle('hidden')">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
        </div>
    </div>

    {{-- Menu mobile (HP/tablet) — isi sama persis dengan menu desktop --}}
    <div id="mobileNav" class="mx-4 hidden rounded-2xl bg-white p-2 shadow-xl ring-1 ring-emerald-100 lg:hidden sm:mx-6">
        @foreach ([
            [route('home').'#beranda', 'Beranda'],
            [route('profil.visi-misi'), 'Profil · Visi & Misi'],
            [route('profil.sejarah'), 'Profil · Sejarah'],
            [route('profil.struktur-organisasi'), 'Profil · Struktur Organisasi'],
            [route('home').'#koleksi', 'Koleksi'],
            [route('home').'#ekatalog', 'E-Resources'],
            [route('home').'#fitur', 'Layanan'],
            [route('panduan.anggota'), 'Panduan Anggota'],
            [route('home').'#kontak', 'Kontak'],
        ] as [$href, $label])
            <a href="{{ $href }}" onclick="document.getElementById('mobileNav').classList.add('hidden')"
               class="block rounded-lg px-4 py-2.5 text-sm font-semibold text-emerald-900 hover:bg-emerald-50">{{ $label }}</a>
        @endforeach
    </div>
</header>

<script>
    (function () {
        var wrap = document.querySelector('[data-profil-dropdown]');
        if (!wrap) return;
        var btn = wrap.querySelector('[data-profil-toggle]');
        var menu = wrap.querySelector('[data-profil-menu]');
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });
        // Klik di luar → tutup
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) menu.classList.add('hidden');
        });
    })();
</script>
