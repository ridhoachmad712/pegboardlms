@extends('layouts.app')

@section('title', 'Kerjakan: ' . $assignment->title)
@section('page-pretitle', $assignment->course->name . ' · Kuis')
@section('page-title', $assignment->title)

@php($secondsLeft = $endsAt ? now()->diffInSeconds($endsAt, false) : null)

@section('content')
<form method="POST" action="{{ route('quizzes.submit', $assignment) }}" id="quiz-form"
      x-data="quizTimer({{ is_null($secondsLeft) ? 'null' : max(0, (int) $secondsLeft) }})" x-init="start()">
    @csrf

    @if (! is_null($secondsLeft))
        <div class="card mb-3 sticky-top">
            <div class="card-body d-flex align-items-center">
                <i class="ti ti-clock fs-2 me-2"></i>
                <div>
                    <div class="text-secondary small">Sisa waktu</div>
                    <div class="h2 mb-0" :class="remaining <= 60 ? 'text-red' : ''" x-text="display"></div>
                </div>
                <button type="submit" class="btn btn-primary ms-auto"><i class="ti ti-send me-1"></i>Kumpulkan</button>
            </div>
        </div>
    @endif

    @foreach ($assignment->questions as $i => $q)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex mb-2">
                    <span class="badge bg-{{ $q->isPg() ? 'blue' : 'purple' }}-lt me-2">Soal {{ $i + 1 }}</span>
                    <span class="text-secondary small ms-auto">{{ $q->points }} poin</span>
                </div>
                <div class="mb-3" style="white-space:pre-line">{{ $q->question }}</div>

                @if ($q->isPg())
                    @foreach ($q->options as $key => $opt)
                        <label class="form-selectgroup-item d-block mb-1">
                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}" class="form-check-input me-2">
                            <strong>{{ $key }}.</strong> {{ $opt }}
                        </label>
                    @endforeach
                @else
                    <textarea name="answers[{{ $q->id }}]" class="form-control" rows="4" placeholder="Tulis jawaban Anda..."></textarea>
                @endif
            </div>
        </div>
    @endforeach

    <div class="card">
        <div class="card-body text-end">
            <button type="submit" class="btn btn-primary btn-lg"><i class="ti ti-send me-1"></i>Kumpulkan Jawaban</button>
        </div>
    </div>
</form>
@endsection

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
@endpush
