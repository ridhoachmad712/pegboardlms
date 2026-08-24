<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable(['course_id', 'number', 'topic', 'type', 'location', 'date', 'attend_opens_at', 'attend_closes_at', 'description'])]
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
            'attend_opens_at' => 'datetime',
            'attend_closes_at' => 'datetime',
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
        if ($this->relationLoaded('tokens')) {
            return $this->tokens
                ->filter(fn (AttendanceToken $token) => $token->expires_at?->isFuture())
                ->sortByDesc('created_at')
                ->first();
        }

        return $this->tokens()->where('expires_at', '>', now())->latest()->first();
    }

    // ================= Jendela absensi otomatis =================

    /** Waktu buka absensi terjadwal — kustom dosen, atau default awal hari tanggal pertemuan. */
    public function attendOpensAt(): ?Carbon
    {
        if ($this->attend_opens_at) {
            return $this->attend_opens_at;
        }

        return $this->date ? $this->date->copy()->startOfDay() : null;
    }

    /** Waktu tutup absensi terjadwal — kustom dosen, atau default akhir hari tanggal pertemuan. */
    public function attendClosesAt(): ?Carbon
    {
        if ($this->attend_closes_at) {
            return $this->attend_closes_at;
        }

        return $this->date ? $this->date->copy()->endOfDay() : null;
    }

    /** Apakah saat ini berada dalam jendela absensi terjadwal. */
    public function scheduledOpen(): bool
    {
        $open = $this->attendOpensAt();
        $close = $this->attendClosesAt();

        return $open && $close && now()->betweenIncluded($open, $close);
    }

    /** Absensi terbuka = ada token manual aktif ATAU sedang dalam jendela terjadwal. */
    public function attendanceOpen(): bool
    {
        return $this->activeToken() !== null || $this->scheduledOpen();
    }

    /**
     * Token aktif untuk sesi. Bila belum ada tapi jendela terjadwal sedang terbuka,
     * buat token otomatis (berlaku sampai jendela tutup) agar QR/kode langsung tersedia.
     */
    public function ensureActiveToken(): ?AttendanceToken
    {
        if ($token = $this->activeToken()) {
            return $token;
        }

        if ($this->scheduledOpen()) {
            return $this->tokens()->create([
                'token' => (string) Str::uuid(),
                'code' => AttendanceToken::generateCode(),
                'expires_at' => $this->attendClosesAt(),
            ]);
        }

        return null;
    }
}
