<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id', 'meeting_id', 'grade_component_id', 'title', 'description',
    'type', 'deadline', 'max_score', 'duration_minutes', 'published',
])]
class Assignment extends Model
{
    public const TYPE_TUGAS = 'tugas';
    public const TYPE_KUIS = 'kuis';

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'published' => 'boolean',
        ];
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
        return $this->submissions()->where('user_id', $user->id)->first();
    }
}
