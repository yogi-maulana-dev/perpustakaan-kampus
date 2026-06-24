<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 16px; color: #4f46e5; }
        .header .sub { margin-top: 2px; font-size: 13px; font-weight: bold; }
        .meta { font-size: 10px; color: #6b7280; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; color: #3730a3; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) td { background: #f9fafb; }
        .text-right { text-align: right; }
        .footer { margin-top: 16px; font-size: 9px; color: #9ca3af; text-align: right; }
        .summary { margin-top: 10px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistem Informasi Perpustakaan Kampus</h1>
        <div class="sub">{{ $title }}</div>
        <div class="meta">
            @if (!empty($from) || !empty($to))
                Periode: {{ $from ?? '…' }} s/d {{ $to ?? '…' }} &middot;
            @endif
            Dicetak: {{ $generatedAt->format('d M Y H:i') }}
        </div>
    </div>

    @yield('content')

    <div class="footer" style="text-align:center; margin-top:18px; border-top:1px solid #e5e7eb; padding-top:6px;">
        Didukung oleh Tim IT dan Pegawai Perpustakaan Universitas Muhammadiyah Lampung<br>
        Dikembangkan oleh yogi-maulana-dev
    </div>
</body>
</html>
