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

@if ($type !== 'kuis')
    <div class="mb-3">
        <label class="form-label required">Bentuk Jawaban</label>
        <select name="submission_mode" class="form-select @error('submission_mode') is-invalid @enderror">
            @foreach (\App\Models\Assignment::SUBMISSION_MODES as $key => $label)
                <option value="{{ $key }}" @selected(old('submission_mode', $assignment->submission_mode ?? 'file') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('submission_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="form-hint">Cara mahasiswa mengumpulkan: unggah berkas, mengetik teks langsung, atau keduanya.</small>
    </div>

    <div class="mb-3" x-data="{ mode: '{{ old('mode', $assignment->mode ?? 'individu') }}' }">
        <label class="form-label required">Bentuk Tugas</label>
        <div class="row g-2">
            <div class="col-md-6">
                <select name="mode" class="form-select" x-model="mode">
                    <option value="individu">Individu</option>
                    <option value="kelompok">Kelompok</option>
                </select>
            </div>
            <div class="col-md-6" x-show="mode === 'kelompok'" x-cloak>
                <div class="input-group">
                    <span class="input-group-text">Maks</span>
                    <input type="number" name="group_max" class="form-control @error('group_max') is-invalid @enderror" min="2" max="20"
                           value="{{ old('group_max', $assignment->group_max ?? 5) }}" placeholder="anggota / kelompok">
                    <span class="input-group-text">anggota</span>
                </div>
                @error('group_max')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
        <small class="form-hint">Kelompok: mahasiswa memilih anggota sendiri dari peserta kelas; cukup 1 orang mengumpulkan untuk seluruh anggota.</small>
    </div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Deadline</label>
        <input type="datetime-local" name="deadline" class="form-control @error('deadline') is-invalid @enderror"
               value="{{ old('deadline', isset($assignment->deadline) ? $assignment->deadline->format('Y-m-d\TH:i') : '') }}">
        @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label required">Nilai Maksimal</label>
        <input type="number" name="max_score" class="form-control @error('max_score') is-invalid @enderror"
               value="{{ old('max_score', $assignment->max_score ?? 100) }}" min="1" max="1000" required>
        @error('max_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if ($type === 'kuis')
        <div class="col-md-6 mb-3">
            <label class="form-label">Durasi Pengerjaan (menit)</label>
            <input type="number" name="duration_minutes" class="form-control @error('duration_minutes') is-invalid @enderror"
                   value="{{ old('duration_minutes', $assignment->duration_minutes ?? '') }}" min="1" max="600"
                   placeholder="Kosongkan = tanpa batas">
            @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif

    <div class="col-md-6 mb-3">
        <label class="form-label">Komponen Nilai</label>
        <select name="grade_component_id" class="form-select @error('grade_component_id') is-invalid @enderror">
            <option value="">— Tidak dikaitkan —</option>
            @foreach ($components as $c)
                <option value="{{ $c->id }}" @selected(old('grade_component_id', $assignment->grade_component_id ?? '') == $c->id)>
                    {{ $c->name }} ({{ $c->weight }}%)
                </option>
            @endforeach
        </select>
        @error('grade_component_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="form-hint">Kaitkan agar nilai masuk ke rekap otomatis.</small>
    </div>
</div>
