@extends('reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th><th>Kode</th><th>Anggota</th><th>Buku</th>
                <th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Tgl Kembali</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loans as $i => $loan)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $loan->kode_pinjam }}</td>
                    <td>{{ $loan->user?->name }}</td>
                    <td>{{ $loan->details->pluck('book.judul')->implode(', ') }}</td>
                    <td>{{ $loan->tanggal_pinjam?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $loan->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $loan->tanggal_kembali?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $loan->status->label() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary"><strong>Total transaksi:</strong> {{ $loans->count() }}</div>
@endsection
