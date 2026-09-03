@extends('layouts.app')

@section('title', 'Kerjakan: ' . $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Kuis')
@section('page-title', $assignment->title)

@php($secondsLeft = $endsAt ? now()->diffInSeconds($endsAt, false) : null)

@section('content')
<form method="POST" action="{{ route('quizzes.submit', $assignment) }}" id="quiz-form" class="quiz-taking"
      data-autosave="quiz-{{ $assignment->id }}"
      data-confirm="Kumpulkan jawaban sekarang? Jawaban tidak dapat diubah setelah dikirim."
      x-data="quizTimer({{ is_null($secondsLeft) ? 'null' : max(0, (int) $secondsLeft) }})" x-init="start()">
    @csrf

    @if (! is_null($secondsLeft))
        <div class="card mb-3 sticky-top quiz-timer-card">
            <div class="card-body d-flex align-items-center">
                <i class="ti ti-clock fs-2 me-2"></i>
                <div>
                    <div class="text-secondary small">Sisa waktu</div>
                    <div class="h2 mb-0" :class="remaining <= 60 ? 'text-red' : ''" x-text="display"></div>
                </div>
                <span class="badge bg-red-lt ms-3" x-show="remaining !== null && remaining <= 60 && remaining > 0" x-cloak><i class="ti ti-alert-triangle me-1"></i>Waktu hampir habis</span>
                <button type="submit" class="btn btn-primary ms-auto" data-loading="Mengirim…"><i class="ti ti-send me-1"></i>Kumpulkan</button>
            </div>
        </div>
    @endif

    {{-- Progres pengerjaan --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="text-secondary small">Progres pengerjaan</span>
                <span class="small"><strong id="quiz-progress-count">0/{{ $assignment->questions->count() }}</strong> soal terjawab</span>
            </div>
            <div class="progress" style="height:.4rem"><div id="quiz-progress-bar" class="progress-bar" style="width:0" role="progressbar" aria-label="Progres pengerjaan kuis"></div></div>
        </div>
    </div>

    @foreach ($assignment->questions as $i => $q)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex mb-2">
                    <span class="badge bg-{{ $q->isPg() ? 'blue' : 'purple' }}-lt me-2">Soal {{ $i + 1 }}</span>
                    <span class="text-secondary small ms-auto">{{ $q->points }} poin</span>
                </div>

                @if ($q->isPg())
                    <fieldset class="border-0 p-0 m-0">
                        <legend class="mb-3 content-prose" style="white-space:pre-line;font-size:1rem;float:none;width:100%;">{{ $q->question }}</legend>
                        @foreach ($q->options as $key => $opt)
                            <label class="quiz-option d-flex align-items-start gap-2 mb-2">
                                <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}" class="form-check-input mt-1 flex-shrink-0">
                                <span class="responsive-item-main"><strong>{{ $key }}.</strong> {{ $opt }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @else
                    <div class="mb-3 content-prose" style="white-space:pre-line">{{ $q->question }}</div>
                    <textarea name="answers[{{ $q->id }}]" class="form-control" rows="4" placeholder="Tulis jawaban Anda..." aria-label="Jawaban soal {{ $i + 1 }}"></textarea>
                @endif
            </div>
        </div>
    @endforeach

    <div class="card quiz-submit-card">
        <div class="card-body text-end">
            <button type="submit" class="btn btn-primary btn-lg" data-loading="Mengirim jawaban…"><i class="ti ti-send me-1"></i>Kumpulkan Jawaban</button>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
.quiz-timer-card{z-index:20;top:.75rem;}
.quiz-option{min-height:3rem;padding:.75rem;border:1px solid var(--tblr-border-color);border-radius:.65rem;cursor:pointer;overflow-wrap:anywhere;}
.quiz-option:has(input:checked){border-color:var(--tblr-primary);background:rgba(var(--tblr-primary-rgb),.08);}
@media(max-width:575.98px){
    body.student-mobile-ui .page-header{display:none;}
    .quiz-timer-card{top:.5rem;}
    .quiz-timer-card .card-body{padding:.75rem;}
    .quiz-timer-card .btn{padding-left:.75rem;padding-right:.75rem;}
    .quiz-submit-card{position:sticky;z-index:20;bottom:calc(4.75rem + env(safe-area-inset-bottom));}
    .quiz-submit-card .btn{width:100%;}
}
</style>
@endpush

@push('scripts')
<script>
function quizTimer(seconds) {
    return {
        remaining: seconds,
        display: '',
        start() {
            if (this.remaining === null) return;
            this.tick();
            this._iv = setInterval(() => this.tick(), 1000);
        },
        tick() {
            if (this.remaining <= 0) {
                clearInterval(this._iv);
                this.display = '00:00';
                // auto-submit sekali
                if (!this._submitted) {
                    this._submitted = true;
                    document.getElementById('quiz-form').submit();
                }
                return;
            }
            const m = Math.floor(this.remaining / 60);
            const s = this.remaining % 60;
            this.display = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            this.remaining--;
        },
    };
}
</script>
<script>
(function () {
    var form = document.getElementById('quiz-form');
    if (!form) return;
    var total = {{ $assignment->questions->count() }};
    var base = 'Kumpulkan jawaban sekarang? Jawaban tidak dapat diubah setelah dikirim.';
    var counter = document.getElementById('quiz-progress-count');
    var bar = document.getElementById('quiz-progress-bar');
    function answered() {
        var groups = {};
        form.querySelectorAll('[name^="answers["]').forEach(function (el) {
            if (el.type === 'radio') { if (el.checked) groups[el.name] = true; }
            else if (el.value && el.value.trim() !== '') { groups[el.name] = true; }
        });
        return Object.keys(groups).length;
    }
    function update() {
        var a = answered();
        if (counter) counter.textContent = a + '/' + total;
        if (bar) { bar.style.width = (total ? Math.round(a / total * 100) : 0) + '%'; bar.setAttribute('aria-valuenow', a); }
        var left = total - a;
        // Pesan konfirmasi (modal data-confirm) menyertakan jumlah soal yang belum dijawab.
        form.setAttribute('data-confirm', base + (left > 0 ? ' Masih ada ' + left + ' soal belum dijawab.' : ''));
    }
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
})();
</script>
@endpush
