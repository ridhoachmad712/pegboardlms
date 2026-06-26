<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'number', 'topic', 'type', 'location', 'date', 'description'])]
class Meeting extends Model
{
    /** @use HasFactory<\Database\Factories\MeetingFactory> */
    use HasFactory;

    public const TYPE_TATAP_MUKA = 'tatap_muka';
    public const TYPE_MANDIRI = 'mandiri';

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function isTatapMuka(): bool
    {
        return $this->type !== self::TYPE_MANDIRI; // default & data lama dianggap tatap muka
    }

    public function isMandiri(): bool
    {
        return $this->type === self::TYPE_MANDIRI;
    }

    public function typeLabel(): string
    {
        return $this->isMandiri() ? 'Mandiri' : 'Tatap Muka';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->latest();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->latest();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(AttendanceToken::class);
    }

    /** Token absensi aktif (belum kedaluwarsa). */
    public function activeToken(): ?AttendanceToken
    {
        return $this->tokens()->where('expires_at', '>', now())->latest()->first();
    }
}
