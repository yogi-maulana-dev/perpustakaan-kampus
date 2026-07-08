@extends('reports.layout')

@section('content')
    {{-- Rekap per anggota: berapa kali diperpanjang --}}
    <div class="summary"><strong>Rekap per Anggota</strong></div>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Anggota</th><th>No. Identitas</th>
                <th>Jumlah Perpanjangan</th><th>Berlaku Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($renewals->groupBy('user_id')->values() as $i => $group)
                @php $u = $group->first()->user; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $u?->name }} @if($u?->email)<br><span style="color:#6b7280">{{ $u->email }}</span>@endif</td>
                    <td>{{ $u?->mahasiswaProfile?->nomorIdentitas() }}</td>
                    <td>{{ $group->count() }} kali</td>
                    <td>{{ $group->max('sampai_tanggal')?->format('d-m-Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada perpanjangan pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Riwayat lengkap --}}
    <div class="summary" style="margin-top:14px"><strong>Riwayat Perpanjangan</strong></div>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Tanggal</th><th>Anggota</th><th>No. Identitas</th>
                <th>Berlaku Lama</th><th>Berlaku Baru</th><th>Diperpanjang Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($renewals as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->created_at->format('d-m-Y H:i') }}</td>
                    <td>{{ $r->user?->name }}</td>
                    <td>{{ $r->user?->mahasiswaProfile?->nomorIdentitas() }}</td>
                    <td>{{ $r->dari_tanggal?->format('d-m-Y') ?? '—' }}</td>
                    <td>{{ $r->sampai_tanggal->format('d-m-Y') }}</td>
                    <td>{{ $r->petugas?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada perpanjangan pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="summary"><strong>Total perpanjangan:</strong> {{ $renewals->count() }} · <strong>Anggota terlibat:</strong> {{ $renewals->pluck('user_id')->unique()->count() }}</div>
@endsection
