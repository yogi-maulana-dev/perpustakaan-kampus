@extends('reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th><th>Anggota</th><th>Kode Pinjam</th>
                <th class="text-right">Hari Telat</th><th class="text-right">Tarif</th>
                <th class="text-right">Total Denda</th><th>Status</th><th>Tgl Bayar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($fines as $i => $fine)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $fine->user?->name }}</td>
                    <td>{{ $fine->loan?->kode_pinjam }}</td>
                    <td class="text-right">{{ $fine->jumlah_hari_telat }}</td>
                    <td class="text-right">{{ number_format($fine->tarif_denda, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($fine->total_denda, 0, ',', '.') }}</td>
                    <td>{{ $fine->status->label() }}</td>
                    <td>{{ $fine->paid_at?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary">
        <strong>Total denda:</strong> Rp {{ number_format($fines->sum('total_denda'), 0, ',', '.') }} &middot;
        <strong>Belum dibayar:</strong> Rp {{ number_format($fines->where('status', \App\Enums\FineStatus::BelumBayar)->sum('total_denda'), 0, ',', '.') }}
    </div>
@endsection
