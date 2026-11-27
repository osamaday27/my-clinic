<?php
namespace App\Services;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PortalAccountService
{
    public function __construct(private AuditService $audit) {}

    public function accountOf(Patient $p): ?User
    {
        return User::withTrashed()->where('patient_id', $p->id)->first();
    }

    /** @return array{0: User, 1: string} the user and the one-time temporary password */
    public function create(Patient $p, string $email): array
    {
        return DB::transaction(function () use ($p, $email) {
            if ($this->accountOf($p)) {
                throw ValidationException::withMessages(['email' => __('clinic.ui.account_exists')]);
            }
            $temp = Str::password(12, symbols: false);
            $user = new User();
            $user->forceFill([
                'name' => $p->name_ar, 'email' => $email, 'password' => $temp, 'role' => Role::Patient->value,
                'patient_id' => $p->id, 'is_active' => true, 'must_change_password' => true,
            ])->save();
            $this->audit->log('portal.account_created', $p);

            return [$user, $temp];
        });
    }

    public function resetPassword(User $u): string
    {
        $temp = Str::password(12, symbols: false);
        $u->forceFill(['password' => $temp, 'must_change_password' => true])->save();
        DB::table('sessions')->where('user_id', $u->id)->delete();           // kick out any live session
        $this->audit->log('portal.password_reset', $u);

        return $temp;
    }

    public function setActive(User $u, bool $active): void
    {
        $u->forceFill(['is_active' => $active])->save();
        $this->audit->log($active ? 'portal.enabled' : 'portal.disabled', $u);
    }
}

/*
SYSTEM DESIGN:
Staff-provisioned portal accounts (no open self-registration -> no account-takeover of someone else's medical file). Temporary password is shown once, stored only as a hash, and must be changed at first login. Reset also revokes live sessions.

DATA STRUCTURE:
1:1 patient <-> user (unique). Sessions table purge by user_id.
*/
