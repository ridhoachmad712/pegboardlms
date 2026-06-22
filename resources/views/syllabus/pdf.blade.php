<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; border-bottom: 1px solid #999; padding-bottom: 3px; margin: 14px 0 6px; }
        .head { text-align: center; border-bottom: 2px solid #206bc4; padding-bottom: 8px; margin-bottom: 12px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #206bc4; color: #fff; }
        .content { white-space: pre-line; }
    </style>
</head>
<body>
    @php($s = $course->syllabus)
    <div class="head">
        @if (! empty($logoData))<img src="{{ $logoData }}" style="height:40px;margin-bottom:4px;">@endif
        <h1>RENCANA PEMBELAJARAN SEMESTER (RPS)</h1>
        <div class="muted">{{ $footerText }}</div>
    </div>

    <table>
        <tr><th style="width:25%">Mata Kuliah</th><td>{{ $course->name }}</td><th style="width:15%">Kode</th><td>{{ $course->code }}</td></tr>
        <tr><th>Semester</th><td>{{ $course->semester }} {{ $course->year }}</td><th>Dosen</th><td>{{ $course->lecturer->name }}</td></tr>
    </table>

    @foreach ([
        ['CPL — Capaian Pembelajaran Lulusan', $s->cpl ?? null, true],
        ['CPMK — Capaian Pembelajaran Mata Kuliah', $s->cpmk ?? null, true],
        ['Sub-CPMK', $s->sub_cpmk ?? null, true],
        ['Deskripsi Mata Kuliah', $s->description ?? null, false],
        ['Referensi / Pustaka', $s->references ?? null, true],
        ['Metode Penilaian', $s->assessment ?? null, false],
        ['Aturan Kelas', $s->rules ?? null, false],
    ] as [$label, $val, $numbered])
        @if ($val)
            <h2>{{ $label }}</h2>
            @if ($numbered)
                <ol style="margin:4px 0 0; padding-left:18px;">
                    @foreach (preg_split('/\r\n|\r|\n/', $val) as $line)
                        @if (trim($line) !== '')<li>{{ trim($line) }}</li>@endif
                    @endforeach
                </ol>
            @else
                <div class="content">{{ $val }}</div>
            @endif
        @endif
    @endforeach

    <h2>Jadwal Pertemuan</h2>
    <table>
        <thead><tr><th style="width:12%">Pertemuan</th><th>Topik</th><th style="width:20%">Tanggal</th></tr></thead>
        <tbody>
            @forelse ($course->meetings as $m)
                <tr><td>{{ $m->number }}</td><td>{{ $m->topic }}</td><td>{{ $m->date?->format('d/m/Y') ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="3" class="muted">Belum ada jadwal.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
