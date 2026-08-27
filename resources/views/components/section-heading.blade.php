@props([
    'title',
    'id' => null,
    'href' => null,
    'action' => null,
])

<div {{ $attributes->class(['mobile-section-heading']) }}>
    <h2 class="h2 mobile-section-heading__title" @if($id) id="{{ $id }}" @endif>{{ $title }}</h2>
    @if ($href && $action)
        <a href="{{ $href }}" class="mobile-section-heading__action">{{ $action }}</a>
    @elseif (! $slot->isEmpty())
        <div>{{ $slot }}</div>
    @endif
</div>
