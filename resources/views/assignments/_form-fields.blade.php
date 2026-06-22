<input type="hidden" name="type" value="{{ $type }}">

<div class="mb-3">
    <label class="form-label required">Pertemuan</label>
    <select name="meeting_id" class="form-select @error('meeting_id') is-invalid @enderror" required>
        <option value="">— Pilih pertemuan —</option>
        @foreach ($meetings as $m)
            <option value="{{ $m->id }}" @selected(old('meeting_id', $assignment->meeting_id ?? ($meetingId ?? '')) == $m->id)>
                Pertemuan {{ $m->number }} — {{ $m->topic }}
            </option>
        @endforeach
    </select>
    @error('meeting_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="form-hint">Tugas/kuis dikelompokkan di bawah pertemuan ini. Mis. UTS di Pertemuan 8, UAS di Pertemuan 16.</small>
</div>

<div class="mb-3">
    <label class="form-label required">Judul</label>
    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $assignment->title ?? '') }}" required>
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Instruksi / Deskripsi</label>
    <textarea name="description" class="form-control" rows="4">{{ old('description', $assignment->description ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Deadline</label>
        <input type="datetime-local" name="deadline" class="form-control"
               value="{{ old('deadline', isset($assignment->deadline) ? $assignment->deadline->format('Y-m-d\TH:i') : '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label required">Nilai Maksimal</label>
        <input type="number" name="max_score" class="form-control"
               value="{{ old('max_score', $assignment->max_score ?? 100) }}" min="1" max="1000" required>
    </div>

    @if ($type === 'kuis')
        <div class="col-md-6 mb-3">
            <label class="form-label">Durasi Pengerjaan (menit)</label>
            <input type="number" name="duration_minutes" class="form-control"
                   value="{{ old('duration_minutes', $assignment->duration_minutes ?? '') }}" min="1" max="600"
                   placeholder="Kosongkan = tanpa batas">
        </div>
    @endif

    <div class="col-md-6 mb-3">
        <label class="form-label">Komponen Nilai</label>
        <select name="grade_component_id" class="form-select">
            <option value="">— Tidak dikaitkan —</option>
            @foreach ($components as $c)
                <option value="{{ $c->id }}" @selected(old('grade_component_id', $assignment->grade_component_id ?? '') == $c->id)>
                    {{ $c->name }} ({{ $c->weight }}%)
                </option>
            @endforeach
        </select>
        <small class="form-hint">Kaitkan agar nilai masuk ke rekap otomatis.</small>
    </div>
</div>
