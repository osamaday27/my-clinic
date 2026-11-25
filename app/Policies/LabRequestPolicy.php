<?php
namespace App\Policies;

use App\Enums\Role;
use App\Models\LabRequest;
use App\Models\User;

class LabRequestPolicy
{
    public function view(User $u, LabRequest $l): bool
    {
        return $u->hasRole(Role::Manager)
            || ($u->hasRole(Role::Doctor) && $u->doctorId() === (int) $l->doctor_id)
            || ($u->hasRole(Role::Nurse) && $l->appointment?->branch_id === $u->branch_id);
    }

    /** Recording results: nurse (own branch) or manager. */
    public function result(User $u, LabRequest $l): bool
    {
        return $u->hasRole(Role::Manager, Role::Nurse) && $this->view($u, $l);
    }
}

/*
SYSTEM DESIGN:
Object-level authorization for lab data: doctor sees only requests he ordered; nurse only own branch; only nurse/manager may record results.

DATA STRUCTURE:
Role x ownership x branch predicates.
*/
