@php($href = $url ?? null)
@if ($href)
    <a href="{{ $href }}" class="card card-sm h-100 card-lift text-reset">
@else
    <div class="card card-sm h-100">
@endif
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="bg-{{ $color }} text-white avatar"><i class="ti {{ $icon }} fs-2"></i></span>
            </div>
            <div class="col">
                <div class="h2 fw-bold m-0 lh-1">{{ $value }}</div>
                <div class="text-secondary">{{ $label }}</div>
            </div>
        </div>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif
