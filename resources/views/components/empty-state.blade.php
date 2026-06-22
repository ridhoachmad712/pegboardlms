@props([
    'icon' => 'ti-mood-empty',
    'title' => 'Belum ada data',
    'description' => null,
])

<div class="empty">
    <div class="empty-icon mb-3" style="width:auto;height:auto;">
        <span class="avatar avatar-xl rounded-circle bg-primary-lt"><i class="ti {{ $icon }}" style="font-size:2rem;"></i></span>
    </div>
    <p class="empty-title">{{ $title }}</p>
    @if ($description)
        <p class="empty-subtitle text-secondary">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="empty-action">
            {{ $slot }}
        </div>
    @endif
</div>
