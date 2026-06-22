<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0; }
        .head { text-align: center; border-bottom: 2px solid #206bc4; padding-bottom: 8px; margin-bottom: 12px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; }
        th { background: #206bc4; color: #fff; }
        td.c, th.c { text-align: center; }
        .sign { margin-top: 36px; width: 100%; }
        .sign td { border: none; text-align: center; }
    </style>
</head>
<body>
    <div class="head">
        @if (! empty($logoData))<img src="{{ $logoData }}" style="height:40px;margin-bottom:4px;">@endif
        <h1>REKAP NILAI</h1>
        <div class="muted">{{ $course->name }} ({{ $course->code }}) · {{ $course->semester }} {{ $course->year }}</div>
        <div class="muted">{{ $footerText }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="c" style="width:5%">No</th>
                <th style="width:18%">NIM</th>
                <th>Nama</th>
                @foreach ($components as $c)<th class="c">{{ $c->name }}<br>({{ $c->weight }}%)</th>@endforeach
                <th class="c">Akhir</th>
                <th class="c">Huruf</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $row['student']->nim_nip }}</td>
                    <td>{{ $row['student']->name }}</td>
                    @foreach ($components as $c)<td class="c">{{ $row['components'][$c->id] ?? '-' }}</td>@endforeach
                    <td class="c">{{ $row['final'] }}</td>
                    <td class="c"><strong>{{ $row['letter'] }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="muted" style="margin-top:8px">
        Rata-rata kelas: {{ $summary['avg'] }} · Tertinggi: {{ $summary['max'] }} · Terendah: {{ $summary['min'] }}
    </div>

    <table class="sign">
        <tr><td></td><td>
            Makassar, {{ now()->translatedFormat('d F Y') }}<br>Dosen Pengampu,<br><br><br><br>
            <strong>{{ $course->lecturer->name }}</strong><br>NIP. {{ $course->lecturer->nim_nip }}
        </td></tr>
    </table>
</body>
</html>
