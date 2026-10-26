<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Kelompok pengerjaan sebuah tugas (bentuk kelompok). Satu kelompok = satu pengumpulan bersama. */
#[Fillable(['assignment_id', 'name', 'created_by'])]
class AssignmentGroup extends Model
{
    /** Tanpa FK level-DB → bersihkan anggota & pengumpulan bersama saat kelompok dihapus. */
    protected static function booted(): void
    {
        static::deleting(function (AssignmentGroup $group) {
            $group->members()->detach();
            $group->submission()->delete();
        });
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Anggota kelompok (mahasiswa peserta kelas). */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assignment_group_user')->withTimestamps()->orderByNim();
    }

    /** Pengumpulan bersama kelompok (satu untuk semua anggota). */
    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }

    public function hasMember(int $userId): bool
    {
        return $this->relationLoaded('members')
            ? $this->members->contains('id', $userId)
            : $this->members()->whereKey($userId)->exists();
    }

    /** Sudah dinilai → susunan anggota terkunci. */
    public function isLocked(): bool
    {
        return ! is_null($this->submission?->score);
    }
}
