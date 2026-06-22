<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['meeting_id', 'title', 'type', 'path', 'url', 'content', 'mime', 'size', 'summary'])]
class Material extends Model
{
    public const TYPE_FILE = 'file';
    public const TYPE_LINK = 'link';
    public const TYPE_VIDEO = 'video';
    public const TYPE_TEXT = 'text';

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }

    public function isText(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    /** URL publik untuk diakses mahasiswa (file lokal atau link eksternal). */
    public function getLinkAttribute(): ?string
    {
        return $this->isFile()
            ? ($this->path ? Storage::disk('public')->url($this->path) : null)
            : $this->url;
    }

    /** Ukuran file terformat (KB/MB). */
    public function getSizeForHumansAttribute(): ?string
    {
        if (! $this->size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }
}
