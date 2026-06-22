@props(['course', 'current' => null])

<li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Kelas Saya</a></li>
<li class="breadcrumb-item {{ $current ? '' : 'active' }}">
    <a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a>
</li>
@if ($current)
    <li class="breadcrumb-item active" aria-current="page">{{ $current }}</li>
@endif
