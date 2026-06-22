@props(['name' => '', 'url' => null, 'size' => 'sm'])

@php
    $colors = ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
    $color = $colors[abs(crc32($name)) % count($colors)];
    $initial = strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
@endphp

<span {{ $attributes->merge(['class' => 'avatar avatar-'.$size.' '.($url ? '' : 'bg-'.$color.'-lt')]) }}
      @if ($url) style="background-image:url('{{ $url }}')" @endif>
    @unless ($url){{ $initial }}@endunless
</span>
