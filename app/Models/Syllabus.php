<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'description', 'cpl', 'cpmk', 'sub_cpmk', 'references', 'assessment', 'rules'])]
class Syllabus extends Model
{
    protected $table = 'syllabi';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Normalkan input bernomor (array baris) atau teks biasa menjadi satu string
     * dipisah baris baru; baris kosong dibuang. Mengembalikan null bila kosong.
     */
    public static function listToText(mixed $value): ?string
    {
        if (is_array($value)) {
            $items = array_values(array_filter(
                array_map(fn ($s) => trim((string) $s), $value),
                fn ($s) => $s !== '',
            ));

            return $items === [] ? null : implode("\n", $items);
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
