<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Anggota — {{ $user->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js" defer></script>
    @php
        use Illuminate\Support\Str;
        use Illuminate\Support\Facades\Storage;
        $tipe = strtoupper($profile->tipe->label());
        $nomor = $profile->nomorIdentitas() ?: 'UML'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT);
        $foto = $profile->foto ? Storage::disk('public')->url($profile->foto) : null;
        $berlaku = $profile->kartuBerlakuSampai()?->format('d-m-Y') ?? '-';
        $prodi = trim(($profile->jenjang ? $profile->jenjang.' ' : '').$profile->program_studi);
    @endphp
    <style>
        html, body, .kartu, .kartu * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .kartu { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="mx-auto max-w-xl px-4 py-8">

        <div class="no-print mb-5 flex items-center justify-between">
            <a href="{{ url()->previous() }}" class="text-sm text-gray-600 hover:underline">&larr; Kembali</a>
            <button onclick="window.print()" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Cetak / Simpan PDF</button>
        </div>

        {{-- KARTU (rasio ~ kartu ID) --}}
        <div class="kartu mx-auto w-[420px] max-w-full overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-emerald-100">
            {{-- Header --}}
            <div class="flex items-center gap-3 bg-gradient-to-r from-emerald-800 to-emerald-600 px-4 py-3 text-white">
                <span class="flex h-10 items-center rounded-lg bg-white px-1.5">
                    <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Logo" class="h-7 w-auto object-contain">
                </span>
                <div class="leading-tight">
                    <p class="text-[11px] font-semibold tracking-wide text-emerald-100">PERPUSTAKAAN</p>
                    <p class="text-sm font-extrabold">UNIVERSITAS MUHAMMADIYAH LAMPUNG</p>
                </div>
                <span class="ml-auto rounded-md bg-yellow-300 px-2 py-1 text-[11px] font-extrabold tracking-wider text-emerald-950">{{ $tipe }}</span>
            </div>

            {{-- Body --}}
            <div class="flex gap-4 px-4 py-4">
                {{-- Foto --}}
                <div class="relative h-28 w-24 shrink-0 overflow-hidden rounded-lg bg-emerald-50 ring-1 ring-emerald-100">
                    <div class="grid h-full place-items-center text-3xl font-bold text-emerald-300">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    @if ($foto)
                        <img src="{{ $foto }}" alt="Foto" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                    @endif
                </div>

                {{-- Data --}}
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-extrabold uppercase leading-tight text-emerald-950">{{ $user->name }}</p>
                    <p class="font-mono text-sm font-semibold tracking-wider text-emerald-700">{{ $nomor }}</p>

                    <dl class="mt-2 grid grid-cols-[5rem_1fr] gap-x-2 gap-y-1 text-[11.5px] leading-snug">
                        @if ($prodi)
                            <dt class="text-gray-400">Prodi</dt><dd class="font-medium text-gray-700">{{ $prodi }}</dd>
                        @endif
                        @if ($profile->fakultas)
                            <dt class="text-gray-400">Fakultas</dt><dd class="font-medium text-gray-700">{{ $profile->fakultas }}</dd>
                        @endif
                        @if ($profile->instansi)
                            <dt class="text-gray-400">Instansi</dt><dd class="font-medium text-gray-700">{{ $profile->instansi }}</dd>
                        @endif
                        <dt class="text-gray-400">Berlaku</dt><dd class="font-medium text-gray-700">s/d {{ $berlaku }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Barcode --}}
            <div class="border-t border-dashed px-4 pb-2 pt-3 text-center">
                <svg id="barcode" class="mx-auto"></svg>
            </div>

            {{-- Kredit (di dalam kartu) --}}
            <div class="bg-emerald-50 px-3 py-1.5 text-center text-[8px] leading-tight text-emerald-700">
                <p>Didukung oleh Tim IT dan Pegawai Perpustakaan Universitas Muhammadiyah Lampung</p>
                <p class="font-semibold">Dikembangkan oleh yogi-maulana-dev</p>
            </div>
        </div>

        {{-- SISI BELAKANG (tata tertib & pengesahan) --}}
        @include('partials.kartu-belakang', ['class' => 'mx-auto mt-5 w-[420px] max-w-full'])

        <p class="no-print mt-4 text-center text-xs text-gray-400">Tunjukkan kartu ini saat meminjam buku di perpustakaan. Cetak bolak-balik: sisi atas bagian depan, sisi bawah bagian belakang.</p>
    </div>

    <script>
        window.addEventListener('load', function () {
            const draw = () => {
                if (!window.JsBarcode) return setTimeout(draw, 80);
                JsBarcode('#barcode', @js((string) $nomor), {
                    format: 'CODE128', width: 2, height: 48, displayValue: true, fontSize: 13, margin: 0,
                });
            };
            draw();
        });
    </script>
</body>
</html>
