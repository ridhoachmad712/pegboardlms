@props(['status', 'score' => null])
@php($map = [
    'not_started' => ['Belum dikerjakan', 'yellow', 'ti-clock'],
    'available' => ['Tersedia', 'blue', 'ti-circle-dot'],
    'submitted' => ['Sudah dikumpulkan', 'azure', 'ti-upload'],
    'late' => ['Terlambat', 'orange', 'ti-alert-triangle'],
    'revision' => ['Perlu revisi', 'orange', 'ti-refresh'],
    'graded' => ['Sudah dinilai'.(!is_null($score) ? ': '.\App\Support\Grades::num($score) : ''), 'green', 'ti-circle-check'],
    'completed' => ['Selesai', 'green', 'ti-circle-check'],
    'overdue' => ['Deadline terlewat', 'red', 'ti-alert-circle'],
    'locked' => ['Belum dibuka', 'secondary', 'ti-lock'],
])
@php([$label, $color, $icon] = $map[$status] ?? [$status, 'secondary', 'ti-info-circle'])
<span {{ $attributes->class("badge bg-{$color}-lt learning-status") }}><i class="ti {{ $icon }} me-1"></i>{{ $label }}</span>
