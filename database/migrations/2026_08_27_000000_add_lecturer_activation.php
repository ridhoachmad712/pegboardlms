<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->index();
            $table->timestamp('lecturer_activated_at')->nullable()->index();
            $table->string('institution')->nullable();
        });

        // Preserve teaching access for existing accounts, never grant admin implicitly.
        // The installation owner is selected explicitly with lms:make-admin.
        DB::table('users')->where('role', 'dosen')->update(['lecturer_activated_at' => now()]);

        Schema::create('lecturer_activation_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'used_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_activation_codes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
            $table->dropIndex(['lecturer_activated_at']);
            $table->dropColumn(['is_admin', 'lecturer_activated_at', 'institution']);
        });
    }
};
