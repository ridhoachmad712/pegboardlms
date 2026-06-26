<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'code', 'class_name', 'join_code', 'semester', 'year', 'description', 'status', 'default_meeting_type'])]
class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    /** @deprecated pakai STATUS_COMPLETED */
    public const STATUS_ARCHIVED = self::STATUS_COMPLETED;

    /** Kode gabung unik (6 karakter, tanpa karakter ambigu). */
    public static function generateJoinCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('join_code', $code)->exists());

        return $code;
    }

    /** Dosen pengampu. */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Mahasiswa yang terdaftar. */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'user_id')
            ->withTimestamps()
            ->withPivot('enrolled_at');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class)->orderBy('number');
    }

    public function gradeComponents(): HasMany
    {
        return $this->hasMany(GradeComponent::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->latest();
    }

    /** Semua submission di kelas ini (lewat assignments) — untuk hitung yang perlu dinilai. */
    public function submissions(): HasManyThrough
    {
        return $this->hasManyThrough(Submission::class, Assignment::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class)->latest();
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function syllabus(): HasOne
    {
        return $this->hasOne(Syllabus::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /** Kelas selesai = terkunci (read-only). */
    public function isLocked(): bool
    {
        return $this->isCompleted();
    }

    /** @deprecated pakai isCompleted() */
    public function isArchived(): bool
    {
        return $this->isCompleted();
    }

    /** Warna aksen deterministik (nama ramp Tabler) berdasarkan nama kelas. */
    public function color(): string
    {
        $colors = ['blue', 'azure', 'indigo', 'purple', 'pink', 'teal', 'green', 'cyan', 'orange'];

        return $colors[abs(crc32($this->name)) % count($colors)];
    }
}
