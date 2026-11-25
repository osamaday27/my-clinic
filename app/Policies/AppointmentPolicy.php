<?php
namespace App\Policies;

use App\Enums\Role;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function create(User $u): bool
    {
        return $u->hasRole(Role::Manager, Role::Reception);
    }

    /** Front-desk actions: check-in, reschedule, cancel. Restricted to the user's own branch. */
    public function manage(User $u, Appointment $a): bool
    {
        return $u->hasRole(Role::Manager, Role::Reception)
            && ($u->hasRole(Role::Manager) || $u->branch_id === $a->branch_id);
    }

    /** Visit chart (vitals/diagnosis/lab). Nurse: own branch; doctor: own appointment; manager: all. */
    public function chart(User $u, Appointment $a): bool
    {
        return $u->hasRole(Role::Manager)
            || ($u->hasRole(Role::Doctor) && $u->doctorId() === (int) $a->doctor_id)
            || ($u->hasRole(Role::Nurse) && $u->branch_id === $a->branch_id);
    }

    /** Clinical actions: start / complete visit. Only the treating doctor (or manager). */
    public function treat(User $u, Appointment $a): bool
    {
        return $u->hasRole(Role::Manager) || ($u->hasRole(Role::Doctor) && $u->doctorId() === (int) $a->doctor_id);
    }
}

/*
SYSTEM DESIGN:
Separates front-desk permissions (manage) from clinical permissions (treat) - least privilege.

DATA STRUCTURE:
Role x branch x ownership matrix evaluated as boolean predicates.
*/
