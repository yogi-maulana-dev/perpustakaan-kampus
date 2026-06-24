@extends('reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th><th>Kode</th><th>Judul</th><th>Kategori</th><th>Penulis</th>
                <th>Penerbit</th><th>Tahun</th><th class="text-right">Stok</th><th class="text-right">Tersedia</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($books as $i => $book)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $book->kode_buku }}</td>
                    <td>{{ $book->judul }}</td>
                    <td>{{ $book->category?->nama }}</td>
                    <td>{{ $book->author?->nama }}</td>
                    <td>{{ $book->publisher?->nama }}</td>
                    <td>{{ $book->tahun_terbit }}</td>
                    <td class="text-right">{{ $book->jumlah_stok }}</td>
                    <td class="text-right">{{ $book->stok_tersedia }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary"><strong>Total judul:</strong> {{ $books->count() }} &middot;
        <strong>Total eksemplar:</strong> {{ $books->sum('jumlah_stok') }}</div>
@endsection
