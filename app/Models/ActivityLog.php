<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'action', 'description'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null; // hanya created_at

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
