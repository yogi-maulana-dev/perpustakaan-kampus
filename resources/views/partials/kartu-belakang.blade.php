{{-- Sisi belakang kartu anggota: tata tertib + pengesahan (data dari menu Pengaturan). --}}
@php
    $kartuBelakang = \App\Models\Setting::kartuBelakang();
    $tanggalCetak = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y');
@endphp
<div class="kartu flex flex-col overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-emerald-100 {{ $class ?? '' }}">
    <div class="bg-gradient-to-r from-emerald-800 to-emerald-600 px-4 py-2.5 text-center text-white">
        <p class="text-sm font-extrabold tracking-wide">TATA TERTIB PERPUSTAKAAN</p>
    </div>

    <div class="flex flex-1 flex-col px-4 py-3">
        <ol class="list-decimal space-y-0.5 pl-4 text-[10px] leading-snug text-gray-700">
            @foreach ($kartuBelakang['tata_tertib'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ol>

        <div class="mt-auto flex justify-end pt-3">
            <div class="min-w-[11rem] text-center text-[10px] leading-tight text-gray-700">
                <p>{{ $kartuBelakang['kota'] }}, {{ $tanggalCetak }}</p>
                <p>{{ $kartuBelakang['jabatan'] }}</p>
                @if ($kartuBelakang['ttd_url'])
                    <img src="{{ $kartuBelakang['ttd_url'] }}" alt="Tanda tangan" class="mx-auto h-12 w-auto object-contain" onerror="this.remove()">
                @else
                    <div class="h-12"></div>
                @endif
                <p class="font-bold underline">{{ $kartuBelakang['nama'] !== '' ? $kartuBelakang['nama'] : '(……………………………)' }}</p>
                @if ($kartuBelakang['nip'] !== '')
                    <p>NIP. {{ $kartuBelakang['nip'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-emerald-50 px-3 py-1.5 text-center text-[8px] leading-tight text-emerald-700">
        <p>Didukung oleh Tim IT dan Pegawai Perpustakaan Universitas Muhammadiyah Lampung</p>
        <p class="font-semibold">Dikembangkan oleh yogi-maulana-dev</p>
    </div>
</div>
