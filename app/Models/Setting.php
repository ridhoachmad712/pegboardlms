<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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

    /** Kunci rahasia (mis. API key AI) yang disimpan terenkripsi dengan APP_KEY. */
    public const SECRET_KEYS = ['ai_key_anthropic', 'ai_key_openai', 'ai_key_gemini', 'ai_api_key'];

    /**
     * Baca nilai terenkripsi. Nilai polos lama (sebelum enkripsi) tetap terbaca;
     * ciphertext yang tak bisa didekripsi (APP_KEY berganti) dianggap kosong.
     */
    public static function secret(string $key): ?string
    {
        $value = static::get($key);
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return static::looksEncrypted($value) ? null : $value;
        }
    }

    public static function putSecret(string $key, ?string $value): void
    {
        static::put($key, $value === null || $value === '' ? $value : Crypt::encryptString($value));
    }

    public static function looksEncrypted(string $value): bool
    {
        $payload = json_decode((string) base64_decode($value, true), true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = static::get($key);

        return $v === null ? $default : ($v === '1' || $v === 'true');
    }
}
