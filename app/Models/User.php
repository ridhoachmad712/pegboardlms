<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'nim_nip', 'phone', 'avatar', 'institution', 'email_notifications', 'notification_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_DOSEN = 'dosen';
    public const ROLE_MAHASISWA = 'mahasiswa';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
            'notification_preferences' => 'array',
            'is_admin' => 'boolean',
            'lecturer_activated_at' => 'datetime',
        ];
    }

    // --- Role helpers ---

    public function isDosen(): bool
    {
        return $this->role === self::ROLE_DOSEN;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === self::ROLE_MAHASISWA;
    }

    public function isAdmin(): bool
    {
        return $this->isDosen() && $this->is_admin;
    }

    public function needsLecturerActivation(): bool
    {
        return $this->isDosen() && $this->lecturer_activated_at === null;
    }

    public function activationCodes(): HasMany
    {
        return $this->hasMany(LecturerActivationCode::class);
    }

    /** URL foto profil, atau null bila belum ada. */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar) : null;
    }

    public function initial(): string
    {
        return strtoupper(mb_substr($this->name, 0, 1));
    }

    public function wantsNotification(string $type): bool
    {
        return (bool) (($this->notification_preferences ?? [])[$type] ?? true);
    }

    // --- Relationships ---

    /** Kelas yang diampu (sebagai dosen). */
    public function teachingCourses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /** Kelas yang diikuti (sebagai mahasiswa). */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withTimestamps()
            ->withPivot('enrolled_at');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** Notifikasi in-app (override relasi bawaan trait Notifiable). */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }
}
