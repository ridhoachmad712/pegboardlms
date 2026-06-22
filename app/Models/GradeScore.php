<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['grade_component_id', 'user_id', 'score'])]
class GradeScore extends Model
{
    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }
}
