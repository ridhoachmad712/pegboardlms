@extends('layouts.app')
@section('title', 'Pencarian')
@section('page-pretitle', 'Pencarian')
@section('page-title', $q !== '' ? 'Hasil pencarian' : 'Pencarian')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <form method="GET" action="{{ route('search') }}" class="card mb-3">
        <div class="card-body">
            <div class="input-group mb-2"><span class="input-group-text"><i class="ti ti-search"></i></span><input type="search" name="q" class="form-control" value="{{ $q }}" placeholder="Cari materi, tugas, pertemuan…" aria-label="Kata pencarian"><button class="btn btn-primary">Cari</button></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label small mb-1" for="search-type">Jenis</label><select id="search-type" name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach (['all'=>'Semua konten','courses'=>'Mata kuliah','assignments'=>'Tugas & kuis','materials'=>'Materi','meetings'=>'Pertemuan','announcements'=>'Pengumuman','forum'=>'Forum','students'=>'Mahasiswa'] as $key=>$label)
                        @if($key !== 'students' || auth()->user()->isDosen())<option value="{{ $key }}" @selected($type===$key)>{{ $label }}</option>@endif
                    @endforeach
                </select></div>
                <div class="col-6"><label class="form-label small mb-1" for="search-course">Mata kuliah</label><select id="search-course" name="course" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Semua</option>@foreach($accessibleCourses as $item)<option value="{{ $item->id }}" @selected($courseId===$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            </div>
        </div>
    </form>

    @php($total = collect([$courses,$assignments,$materials,$meetings,$announcements,$threads,$students])->sum(fn($items) => $items->count()))
    @if ($q !== '' && $total === 0)<div class="card"><div class="card-body"><x-empty-state icon="ti-search-off" title="Tidak ada hasil" description="Coba kata kunci, jenis konten, atau mata kuliah lain." /></div></div>@endif

    @php($sections = [
        ['Mata Kuliah',$courses,'ti-school','primary',fn($item)=>route('courses.show',$item),fn($item)=>$item->name,fn($item)=>$item->code],
        ['Tugas & Kuis',$assignments,'ti-checklist','orange',fn($item)=>route('assignments.show',$item),fn($item)=>$item->title,fn($item)=>$item->course->name],
        ['Materi',$materials,'ti-folder','blue',fn($item)=>route('materials.preview',$item),fn($item)=>$item->title,fn($item)=>$item->meeting->course->name.' · P'.$item->meeting->number],
        ['Pertemuan',$meetings,'ti-calendar-event','azure',fn($item)=>route('courses.show',$item->course),fn($item)=>$item->topic,fn($item)=>$item->course->name.' · Pertemuan '.$item->number],
        ['Pengumuman',$announcements,'ti-speakerphone','yellow',fn($item)=>route('announcements.index',$item->course),fn($item)=>$item->title,fn($item)=>$item->course->name],
        ['Forum',$threads,'ti-messages','purple',fn($item)=>route('forum.show',$item),fn($item)=>$item->title,fn($item)=>$item->course->name],
    ])
    @foreach($sections as [$label,$items,$icon,$color,$url,$title,$subtitle])
        @if($items->isNotEmpty())<section class="card mb-3 overflow-hidden"><div class="card-header"><h2 class="card-title">{{ $label }}</h2><span class="badge bg-secondary-lt ms-auto">{{ $items->count() }}</span></div><div class="list-group list-group-flush">
            @foreach($items as $item)<a href="{{ $url($item) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3"><span class="avatar avatar-sm bg-{{ $color }}-lt flex-shrink-0"><i class="ti {{ $icon }}"></i></span><span class="responsive-item-main"><span class="d-block fw-bold responsive-item-title">{{ $title($item) }}</span><span class="d-block small text-secondary responsive-item-title">{{ $subtitle($item) }}</span></span><i class="ti ti-chevron-right text-secondary flex-shrink-0"></i></a>@endforeach
        </div></section>@endif
    @endforeach

    @if($students->isNotEmpty())<section class="card mb-3 overflow-hidden"><div class="card-header"><h2 class="card-title">Mahasiswa</h2></div><div class="list-group list-group-flush">@foreach($students as $student)<div class="list-group-item d-flex align-items-center gap-3"><x-avatar :name="$student->name" :url="$student->avatarUrl()" /><span class="responsive-item-main"><span class="d-block fw-bold responsive-item-title">{{ $student->name }}</span><span class="d-block small text-secondary responsive-item-meta"><span>{{ $student->nim_nip }}</span><span>{{ $student->email }}</span></span></span></div>@endforeach</div></section>@endif
</div></div>
@endsection
