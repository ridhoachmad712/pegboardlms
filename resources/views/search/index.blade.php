@extends('layouts.app')

@section('title', 'Pencarian')
@section('page-pretitle', 'Pencarian')
@section('page-title', $q !== '' ? 'Hasil untuk “'.$q.'”' : 'Pencarian')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <form method="GET" action="{{ route('search') }}" class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Cari kelas, tugas/kuis, atau mahasiswa…" autofocus>
                <button class="btn btn-primary">Cari</button>
            </div>
        </form>

        @php($total = $courses->count() + $assignments->count() + $students->count())
        @if ($q !== '' && $total === 0)
            <div class="card"><div class="card-body"><x-empty-state icon="ti-search-off" title="Tidak ada hasil" description="Coba kata kunci lain." /></div></div>
        @endif

        @if ($courses->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Kelas</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($courses as $c)
                        <a href="{{ route('courses.show', $c) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-primary-lt me-2"><i class="ti ti-school"></i></span>
                            <div><div class="fw-bold">{{ $c->name }}</div><div class="small text-secondary">{{ $c->code }} · {{ $c->semester }} {{ $c->year }}</div></div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($assignments->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Tugas & Kuis</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($assignments as $a)
                        <a href="{{ route('assignments.show', $a) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="avatar bg-{{ $a->isQuiz() ? 'purple' : 'blue' }}-lt me-2"><i class="ti {{ $a->isQuiz() ? 'ti-help-circle' : 'ti-file-text' }}"></i></span>
                            <div><div class="fw-bold">{{ $a->title }}</div><div class="small text-secondary">{{ $a->course->name }}</div></div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($students->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Mahasiswa</h3></div>
                <div class="list-group list-group-flush">
                    @foreach ($students as $s)
                        <div class="list-group-item d-flex align-items-center">
                            <span class="avatar bg-secondary-lt me-2">{{ strtoupper(mb_substr($s->name,0,1)) }}</span>
                            <div><div class="fw-bold">{{ $s->name }}</div><div class="small text-secondary">{{ $s->nim_nip }} · {{ $s->email }}</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
