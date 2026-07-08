<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Kartu Anggota ({{ $users->count() }})</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js" defer></script>
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp
    <style>
        html, body, .kartu, .kartu * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .kartu { box-shadow: none !important; break-inside: avoid; page-break-inside: avoid; }
        }
        .kartu { break-inside: avoid; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <div class="no-print mb-5 flex items-center justify-between">
            <a href="{{ url()->previous() }}" class="text-sm text-gray-600 hover:underline">&larr; Kembali</a>
            <div class="text-sm text-gray-500">{{ $users->count() }} kartu siap dicetak</div>
            <button onclick="window.print()" class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Cetak / Simpan PDF</button>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            @foreach ($users as $user)
                @php
                    $profile = $user->mahasiswaProfile;
                    $tipe = strtoupper($profile->tipe->label());
                    $nomor = $profile->nomorIdentitas() ?: 'UML'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT);
                    $foto = $profile->foto ? Storage::disk('public')->url($profile->foto) : null;
                    $berlaku = $profile->kartuBerlakuSampai()?->format('d-m-Y') ?? '-';
                    $prodi = trim(($profile->jenjang ? $profile->jenjang.' ' : '').$profile->program_studi);
                @endphp
                <div class="kartu overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-emerald-100">
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

                    <div class="flex gap-4 px-4 py-4">
                        <div class="relative h-28 w-24 shrink-0 overflow-hidden rounded-lg bg-emerald-50 ring-1 ring-emerald-100">
                            <div class="grid h-full place-items-center text-3xl font-bold text-emerald-300">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            @if ($foto)
                                <img src="{{ $foto }}" alt="Foto" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-extrabold uppercase leading-tight text-emerald-950">{{ $user->name }}</p>
                            <p class="font-mono text-sm font-semibold tracking-wider text-emerald-700">{{ $nomor }}</p>
                            <dl class="mt-2 grid grid-cols-[4.5rem_1fr] gap-x-2 gap-y-0.5 text-[11px] leading-snug">
                                @if ($prodi)<dt class="text-gray-400">Prodi</dt><dd class="font-medium text-gray-700">{{ $prodi }}</dd>@endif
                                @if ($profile->fakultas)<dt class="text-gray-400">Fakultas</dt><dd class="font-medium text-gray-700">{{ $profile->fakultas }}</dd>@endif
                                @if ($profile->instansi)<dt class="text-gray-400">Instansi</dt><dd class="font-medium text-gray-700">{{ $profile->instansi }}</dd>@endif
                                <dt class="text-gray-400">Berlaku</dt><dd class="font-medium text-gray-700">s/d {{ $berlaku }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="border-t border-dashed px-4 pb-2 pt-3 text-center">
                        <svg class="barcode mx-auto" data-number="{{ $nomor }}"></svg>
                    </div>
                    <div class="bg-emerald-50 px-3 py-1.5 text-center text-[8px] leading-tight text-emerald-700">
                        <p>Didukung oleh Tim IT dan Pegawai Perpustakaan Universitas Muhammadiyah Lampung</p>
                        <p class="font-semibold">Dikembangkan oleh yogi-maulana-dev</p>
                    </div>
                </div>

                {{-- Sisi belakang kartu (berdampingan dengan sisi depan) --}}
                @include('partials.kartu-belakang')
            @endforeach
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            const draw = () => {
                if (!window.JsBarcode) return setTimeout(draw, 80);
                document.querySelectorAll('.barcode').forEach((el) => {
                    JsBarcode(el, el.dataset.number, { format: 'CODE128', width: 1.8, height: 42, displayValue: true, fontSize: 12, margin: 0 });
                });
            };
            draw();
        });
    </script>
</body>
</html>
