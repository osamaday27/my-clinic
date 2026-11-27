<?php
namespace App\Policies;

use App\Enums\Role;
use App\Models\InsuranceClaim;
use App\Models\User;

class ClaimPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasRole(Role::Manager, Role::Accountant, Role::Reception);
    }

    /** Reception: read-only. Accountant/manager: submit, decide, record payments. Branch-restricted (except manager). */
    public function manage(User $u, InsuranceClaim $c): bool
    {
        return $u->hasRole(Role::Manager, Role::Accountant)
            && ($u->hasRole(Role::Manager) || $u->branch_id === $c->invoice?->branch_id);
    }
}

/*
SYSTEM DESIGN:
Separation of duties: front-desk can look up claim status but cannot change money-related states.

DATA STRUCTURE:
Role x branch predicate.
*/
