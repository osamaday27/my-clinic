<?php
namespace App\Support;

use App\Enums\Role;
use App\Models\Branch;

class CurrentBranch
{
    /** Non-managers are LOCKED to their own branch; managers may switch via the top bar. */
    public static function id(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        if ($user->role !== Role::Manager) {
            return $user->branch_id;
        }

        return session('branch_id') ?: $user->branch_id ?: Branch::query()->value('id');
    }
}

/*
SYSTEM DESIGN:
Tenant-style data partition resolved server-side. The branch id is NEVER taken from request input for non-managers (prevents horizontal privilege escalation).

DATA STRUCTURE:
Single resolved scalar per request; session stores the manager's choice.
*/
