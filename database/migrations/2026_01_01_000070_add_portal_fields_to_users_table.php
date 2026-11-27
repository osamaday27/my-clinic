<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedBigInteger('patient_id')->nullable()->unique();     // 1 portal account per patient file
            $t->boolean('must_change_password')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['patient_id', 'must_change_password']));
    }
};

/*
SYSTEM DESIGN:
Links a login (role=patient) to exactly one patient file. unique(patient_id) prevents two accounts sharing one medical record. must_change_password forces replacement of the temporary password handed over by reception.

DATA STRUCTURE:
1:1 relation users.patient_id -> patients.id (unique index; no FK for SQLite ALTER compatibility, enforced in PortalAccountService).
*/
