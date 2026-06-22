<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['assignment_id', 'question', 'type', 'options', 'correct_answer', 'points'])]
class QuizQuestion extends Model
{
    public const TYPE_PG = 'pg';
    public const TYPE_ESSAY = 'essay';

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function isPg(): bool
    {
        return $this->type === self::TYPE_PG;
    }
}
