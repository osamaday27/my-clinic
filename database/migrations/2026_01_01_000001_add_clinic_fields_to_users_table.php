<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('reception')->index()->after('password');
            $t->unsignedBigInteger('branch_id')->nullable()->index()->after('role');
            $t->string('phone', 20)->nullable()->after('branch_id');
            $t->boolean('is_active')->default(true)->after('phone');
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropSoftDeletes();
            $t->dropColumn(['role', 'branch_id', 'phone', 'is_active']);
        });
    }
};

/*
SYSTEM DESIGN:
Extends Laravel's default users table with RBAC role + branch scoping + soft delete (medical staff accounts are never hard-deleted).

DATA STRUCTURE:
users(role idx, branch_id idx, is_active). B-tree indexes on role and branch_id for fast filtered staff lists.
*/
