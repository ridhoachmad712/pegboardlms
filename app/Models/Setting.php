<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @var array<string,string>|null */
    protected static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (static::$cache === null) {
            try {
                static::$cache = static::pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                static::$cache = [];
            }
        }

        return static::$cache[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache = null;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = static::get($key);

        return $v === null ? $default : ($v === '1' || $v === 'true');
    }
}
