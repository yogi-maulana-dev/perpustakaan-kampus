@extends('reports.layout')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th><th>Tipe</th><th>No. Identitas</th><th>Nama</th><th>Email</th>
                <th>Fakultas</th><th>Prodi / Instansi</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->mahasiswaProfile?->tipe?->label() }}</td>
                    <td>{{ $s->mahasiswaProfile?->nomorIdentitas() }}</td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $s->email }}</td>
                    <td>{{ $s->mahasiswaProfile?->fakultas }}</td>
                    <td>{{ $s->mahasiswaProfile?->program_studi ? trim(($s->mahasiswaProfile?->jenjang ? $s->mahasiswaProfile->jenjang.' ' : '').$s->mahasiswaProfile->program_studi) : $s->mahasiswaProfile?->instansi }}</td>
                    <td>{{ $s->status->label() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary"><strong>Total mahasiswa:</strong> {{ $students->count() }}</div>
@endsection
