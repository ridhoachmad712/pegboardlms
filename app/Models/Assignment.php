<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id', 'meeting_id', 'grade_component_id', 'title', 'description',
    'type', 'delivery_mode', 'external_url', 'mode', 'group_max', 'submission_mode', 'deadline', 'max_score', 'duration_minutes', 'published',
])]
class Assignment extends Model
{
    public const TYPE_TUGAS = 'tugas';

    public const TYPE_KUIS = 'kuis';

    public const DELIVERY_INTERNAL = 'internal';

    public const DELIVERY_EXTERNAL = 'external';

    /** Bentuk pengerjaan tugas. */
    public const MODE_INDIVIDU = 'individu';

    public const MODE_KELOMPOK = 'kelompok';

    /** Bentuk jawaban tugas. */
    public const SUBMISSION_FILE = 'file';

    public const SUBMISSION_TEXT = 'text';

    public const SUBMISSION_BOTH = 'both';

    public const SUBMISSION_MODES = [
        self::SUBMISSION_FILE => 'Unggah berkas',
        self::SUBMISSION_TEXT => 'Teks langsung',
        self::SUBMISSION_BOTH => 'Teks + berkas',
    ];

    /** Boleh unggah berkas (mode berkas atau keduanya). */
    public function allowsFile(): bool
    {
        return in_array($this->submission_mode, [self::SUBMISSION_FILE, self::SUBMISSION_BOTH], true);
    }

    /** Boleh menulis teks langsung (mode teks atau keduanya). */
    public function allowsText(): bool
    {
        return in_array($this->submission_mode, [self::SUBMISSION_TEXT, self::SUBMISSION_BOTH], true);
    }

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'published' => 'boolean',
            'group_max' => 'integer',
        ];
    }

    /** Tanpa FK level-DB → hapus kelompok (beserta anggota & pengumpulan) saat tugas dihapus. */
    protected static function booted(): void
    {
        static::deleting(function (Assignment $assignment) {
            $assignment->groups()->get()->each->delete();
        });
    }

    /** Tugas dikerjakan berkelompok (bukan individu). */
    public function isGroup(): bool
    {
        return $this->mode === self::MODE_KELOMPOK;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function gradeComponent(): BelongsTo
    {
        return $this->belongsTo(GradeComponent::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** Kelompok-kelompok pada tugas ini (bentuk kelompok). */
    public function groups(): HasMany
    {
        return $this->hasMany(AssignmentGroup::class);
    }

    /** Kelompok tempat $user berada pada tugas ini (atau null). */
    public function groupFor(User $user): ?AssignmentGroup
    {
        return $this->groups()
            ->whereHas('members', fn ($q) => $q->whereKey($user->id))
            ->first();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function rubricCriteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('position')->orderBy('id');
    }

    public function isQuiz(): bool
    {
        return $this->type === self::TYPE_KUIS;
    }

    public function isExternalQuiz(): bool
    {
        return $this->isQuiz() && $this->delivery_mode === self::DELIVERY_EXTERNAL;
    }

    public function isInternalQuiz(): bool
    {
        return $this->isQuiz() && ! $this->isExternalQuiz();
    }

    /** Apakah tugas ini dinilai dengan rubrik (punya kriteria). */
    public function hasRubric(): bool
    {
        return $this->rubricCriteria()->exists();
    }

    public function isPastDeadline(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    /** Total poin soal kuis (untuk normalisasi skor). */
    public function totalPoints(): int
    {
        return (int) $this->questions()->sum('points');
    }

    public function submissionFor(User $user): ?Submission
    {
        // Tugas kelompok: pengumpulan bersama diambil dari kelompok si mahasiswa.
        if ($this->isGroup()) {
            return $this->groupFor($user)?->submission()->first();
        }

        return $this->submissions()->where('user_id', $user->id)->first();
    }
}
