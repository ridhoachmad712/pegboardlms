<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['assignment_id', 'name', 'max_points', 'position'])]
class RubricCriterion extends Model
{
    protected function casts(): array
    {
        return ['max_points' => 'float'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RubricScore::class);
    }
}
