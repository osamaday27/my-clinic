<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->text('two_factor_secret')->nullable();            // encrypted cast
            $t->text('two_factor_recovery_codes')->nullable();    // encrypted cast: JSON list of SHA-256 hashes
            $t->timestamp('two_factor_confirmed_at')->nullable();
            $t->unsignedBigInteger('two_factor_last_step')->nullable();   // replay protection
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_step']);
        });
    }
};

/*
SYSTEM DESIGN:
2FA columns on users. The TOTP secret and recovery-code hashes are encrypted at rest (double protection: DB leak alone is useless). two_factor_last_step stores the last accepted 30s time-step so a captured code cannot be replayed.

DATA STRUCTURE:
last_step = monotonic counter (BIGINT). Recovery codes = encrypted JSON array of SHA-256 digests (single-use, removed on consumption).
*/
