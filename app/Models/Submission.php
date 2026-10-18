<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'assignment_id', 'user_id', 'assignment_group_id', 'file_path', 'file_path_2', 'answer_text', 'score',
    'feedback', 'status', 'started_at', 'submitted_at',
])]
class Submission extends Model
{
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Kelompok pemilik pengumpulan (untuk tugas kelompok). */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AssignmentGroup::class, 'assignment_group_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function rubricScores(): HasMany
    {
        return $this->hasMany(RubricScore::class);
    }

    public function isGraded(): bool
    {
        return ! is_null($this->score);
    }

    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    /** @return array<int, string> Nomor slot => path berkas. */
    public function filePaths(): array
    {
        return array_filter([
            1 => $this->file_path,
            2 => $this->file_path_2,
        ]);
    }

    public function filePath(int $slot): ?string
    {
        return $slot === 2 ? $this->file_path_2 : $this->file_path;
    }

    public function hasFiles(): bool
    {
        return $this->file_path !== null || $this->file_path_2 !== null;
    }
}
