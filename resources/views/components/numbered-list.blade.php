@props(['name', 'label', 'value' => '', 'placeholder' => '', 'hint' => null])
@php
    // Terima string (dipisah baris) maupun array (mis. dari old() saat validasi gagal).
    if (is_array($value)) {
        $__items = array_map(fn ($s) => trim((string) $s), $value);
    } else {
        $__items = preg_split('/\r\n|\r|\n/', (string) $value);
    }
    $__items = array_values(array_filter($__items, fn ($s) => trim((string) $s) !== ''));
    if ($__items === []) {
        $__items = [''];
    }
@endphp
<div class="mb-3" x-data="{ items: {{ \Illuminate\Support\Js::from($__items) }} }">
    <label class="form-label">{{ $label }}</label>
    <template x-for="(item, i) in items" :key="i">
        <div class="input-group mb-1">
            <span class="input-group-text" style="min-width:2.75rem;justify-content:center" x-text="(i + 1) + '.'"></span>
            <input type="text" class="form-control" name="{{ $name }}[]" x-model="items[i]" placeholder="{{ $placeholder }}">
            <button type="button" class="btn btn-outline-danger btn-icon" @click="items.splice(i, 1); items.length || items.push('')" title="Hapus baris">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </template>
    <button type="button" class="btn btn-sm btn-outline-primary" @click="items.push('')">
        <i class="ti ti-plus me-1"></i>Tambah baris
    </button>
    @if ($hint)<div class="form-hint mt-1">{{ $hint }}</div>@endif
</div>
