<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/** Enkripsi API key AI yang selama ini tersimpan polos di tabel settings (ikut terbawa backup). */
return new class extends Migration
{
    public function up(): void
    {
        $this->rows()->each(function ($row) {
            if (! Setting::looksEncrypted($row->value)) {
                DB::table('settings')->where('id', $row->id)->update(['value' => Crypt::encryptString($row->value)]);
            }
        });
    }

    public function down(): void
    {
        $this->rows()->each(function ($row) {
            if (Setting::looksEncrypted($row->value)) {
                DB::table('settings')->where('id', $row->id)->update(['value' => Crypt::decryptString($row->value)]);
            }
        });
    }

    private function rows()
    {
        return DB::table('settings')
            ->whereIn('key', Setting::SECRET_KEYS)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->get(['id', 'value']);
    }
};
