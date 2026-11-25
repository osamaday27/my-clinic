<?php
namespace App\Policies;

use App\Enums\Role;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $u): bool
    {
        return $u->hasRole(Role::Manager, Role::Accountant, Role::Reception);
    }

    public function pay(User $u, Invoice $i): bool
    {
        return $this->viewAny($u) && ($u->hasRole(Role::Manager) || $u->branch_id === $i->branch_id);
    }
}

/*
SYSTEM DESIGN:
Financial actions limited to finance roles and own branch.

DATA STRUCTURE:
Role x branch predicate.
*/
