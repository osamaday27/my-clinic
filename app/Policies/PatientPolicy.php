<?php
namespace App\Policies;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasRole(Role::Manager, Role::Reception, Role::Doctor, Role::Nurse);
    }

    public function create(User $u): bool
    {
        return $u->hasRole(Role::Manager, Role::Reception);
    }

    public function managePortal(User $u, Patient $p): bool
    {
        return $u->hasRole(Role::Manager, Role::Reception);
    }

    public function update(User $u, Patient $p): bool
    {
        return $u->hasRole(Role::Manager, Role::Reception, Role::Nurse);
    }

    public function view(User $u, Patient $p): bool
    {
        if ($u->hasRole(Role::Manager, Role::Reception, Role::Nurse, Role::Accountant)) {
            return true;
        }

        // Doctors: only patients they have/had an appointment with (IDOR protection).
        return $u->hasRole(Role::Doctor)
            && $p->appointments()->where('doctor_id', $u->doctorId() ?? 0)->exists();
    }
}

/*
SYSTEM DESIGN:
Authorization policy (Rule 40): object-level checks stop IDOR - guessing /patients/42 returns 403 for a doctor who never treated patient 42.

DATA STRUCTURE:
Predicate over role enum + EXISTS subquery on appointments(doctor_id).
*/
