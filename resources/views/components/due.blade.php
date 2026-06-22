@props(['date' => null])

@php
    if (! $date) {
        $label = 'Tanpa deadline';
        $color = 'secondary';
        $icon = 'ti-clock-off';
    } else {
        $d = $date instanceof \Illuminate\Support\Carbon ? $date : \Illuminate\Support\Carbon::parse($date);
        $diff = now()->startOfDay()->diffInDays($d->copy()->startOfDay(), false);
        if ($d->isPast()) {
            $label = 'Terlewat';
            $color = 'red';
            $icon = 'ti-alert-triangle';
        } elseif ($diff === 0) {
            $label = 'Hari ini';
            $color = 'orange';
            $icon = 'ti-clock';
        } elseif ($diff === 1) {
            $label = 'Besok';
            $color = 'orange';
            $icon = 'ti-clock';
        } elseif ($diff <= 7) {
            $label = $diff.' hari lagi';
            $color = 'yellow';
            $icon = 'ti-clock';
        } else {
            $label = $d->translatedFormat('d M Y');
            $color = 'secondary';
            $icon = 'ti-calendar';
        }
    }
@endphp

<span {{ $attributes->merge(['class' => 'badge bg-'.$color.'-lt']) }}><i class="ti {{ $icon }} me-1"></i>{{ $label }}</span>
