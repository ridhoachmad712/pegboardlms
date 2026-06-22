<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['year', 'semester'])]
class Semester extends Model
{
    protected $casts = [
        'year' => 'integer',
    ];

    /** Label tampilan, mis. "Ganjil 2025". */
    public function label(): string
    {
        return $this->semester.' '.$this->year;
    }
}
