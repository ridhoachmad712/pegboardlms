<input type="hidden" name="type" value="{{ $type }}">

<div x-data="{ deliveryMode: '{{ old('delivery_mode', $assignment->delivery_mode ?? \App\Models\Assignment::DELIVERY_INTERNAL) }}' }">

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

@if ($type === 'kuis')
    <div class="mb-3">
        <label class="form-label required">Metode Kuis</label>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-selectgroup-item w-100">
                    <input type="radio" name="delivery_mode" value="internal" class="form-selectgroup-input"
                           x-model="deliveryMode" @checked(old('delivery_mode', $assignment->delivery_mode ?? 'internal') === 'internal')>
                    <span class="form-selectgroup-label d-flex align-items-start gap-2 h-100">
                        <i class="ti ti-device-laptop fs-2 text-primary"></i>
                        <span><strong class="d-block">Di LMS</strong><span class="small text-secondary">Soal dan pengerjaan dikelola di sini.</span></span>
                    </span>
                </label>
            </div>
            <div class="col-md-6">
                <label class="form-selectgroup-item w-100">
                    <input type="radio" name="delivery_mode" value="external" class="form-selectgroup-input"
                           x-model="deliveryMode" @checked(old('delivery_mode', $assignment->delivery_mode ?? 'internal') === 'external')>
                    <span class="form-selectgroup-label d-flex align-items-start gap-2 h-100">
                        <i class="ti ti-external-link fs-2 text-purple"></i>
                        <span><strong class="d-block">Platform eksternal</strong><span class="small text-secondary">Wayground, Google Forms, atau platform lain.</span></span>
                    </span>
                </label>
            </div>
        </div>
        @error('delivery_mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3" x-show="deliveryMode === 'external'" x-cloak>
        <label class="form-label required">Tautan Kuis</label>
        <input type="url" name="external_url" class="form-control @error('external_url') is-invalid @enderror"
               value="{{ old('external_url', $assignment->external_url ?? '') }}" placeholder="https://wayground.com/..."
               :required="deliveryMode === 'external'">
        @error('external_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="form-hint">Mahasiswa akan membuka kuis melalui tautan ini. Nilainya diinput dosen setelah kuis selesai.</small>
    </div>
@endif

<div class="mb-3">
    <label class="form-label">Instruksi / Deskripsi</label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ old('description', $assignment->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

@if ($type !== 'kuis')
    <div class="mb-3" x-data="{ fileName: '' }">
        <label for="instruction-file" class="form-label">Lampiran Instruksi <span class="text-secondary">(opsional)</span></label>
        @if(isset($assignment) && $assignment->hasInstructionFile())
            <div class="list-group mb-2">
                <a href="{{ route('assignments.instruction.download', $assignment) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
                    <span class="avatar avatar-sm bg-blue-lt"><i class="ti ti-file-download" aria-hidden="true"></i></span>
                    <span class="flex-fill min-w-0">
                        <span class="d-block fw-semibold text-truncate">{{ $assignment->instruction_file_name }}</span>
                        <span class="d-block text-secondary small">{{ $assignment->instructionFileSizeLabel() ?: 'Berkas instruksi' }} · ketuk untuk mengunduh</span>
                    </span>
                    <i class="ti ti-download text-secondary" aria-hidden="true"></i>
                </a>
            </div>
            <label class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="remove_instruction_file" value="1" @checked(old('remove_instruction_file'))>
                <span class="form-check-label">Hapus lampiran saat disimpan</span>
            </label>
        @endif
        <input id="instruction-file" type="file" name="instruction_file"
               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.zip"
               class="form-control @error('instruction_file') is-invalid @enderror"
               @change="fileName = $event.target.files[0]?.name || ''">
        @error('instruction_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div x-show="fileName" x-cloak class="small text-blue mt-2"><i class="ti ti-file-check me-1" aria-hidden="true"></i><span x-text="fileName"></span></div>
        <small class="form-hint">PDF, Word, PowerPoint, Excel, CSV, TXT, JPG/PNG, atau ZIP. Maksimal 20 MB. File baru akan mengganti lampiran lama.</small>
    </div>

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
        <div class="col-md-6 mb-3" x-show="deliveryMode === 'internal'" x-cloak>
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
</div>
