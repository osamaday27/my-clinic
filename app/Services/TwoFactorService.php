<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class TwoFactorService
{
    public function __construct(private Totp $totp, private AuditService $audit) {}

    public function totp(): Totp
    {
        return $this->totp;
    }

    /** Step 1: create an UNCONFIRMED secret. Login is not affected until confirm() succeeds. */
    public function begin(User $u): string
    {
        $secret = $this->totp->generateSecret();
        $u->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_step' => null])->save();

        return $secret;
    }

    public function uri(User $u): string
    {
        return $this->totp->uri(config('clinic.two_factor.issuer'), $u->email, $u->two_factor_secret);
    }

    /** Step 2: prove the app works, then activate and return one-time recovery codes (plaintext, shown once). */
    public function confirm(User $u, string $code): ?array
    {
        $step = $u->two_factor_secret ? $this->totp->verify($u->two_factor_secret, $code) : null;
        if ($step === null) {
            return null;
        }
        $codes = $this->newRecoveryCodes();
        $u->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step' => $step,
            'two_factor_recovery_codes' => array_map([$this, 'digest'], $codes),
        ])->save();
        $this->audit->log('2fa.enabled', $u);

        return $codes;
    }

    public function regenerate(User $u): array
    {
        $codes = $this->newRecoveryCodes();
        $u->forceFill(['two_factor_recovery_codes' => array_map([$this, 'digest'], $codes)])->save();
        $this->audit->log('2fa.recovery_regenerated', $u);

        return $codes;
    }

    public function disable(User $u): void
    {
        $u->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null, 'two_factor_last_step' => null])->save();
        $this->audit->log('2fa.disabled', $u);
    }

    /** Verifies a TOTP and atomically consumes its time-step (a second use of the same code fails). */
    public function verifyAndConsume(User $u, string $code): bool
    {
        if (! $u->hasTwoFactor()) {
            return false;
        }
        $step = $this->totp->verify($u->two_factor_secret, $code, $u->two_factor_last_step);
        if ($step === null) {
            return false;
        }

        return User::query()->whereKey($u->id)
            ->where(fn ($q) => $q->whereNull('two_factor_last_step')->orWhere('two_factor_last_step', '<', $step))
            ->update(['two_factor_last_step' => $step]) === 1;
    }

    public function useRecoveryCode(User $u, string $code): bool
    {
        $digest = $this->digest(strtolower(trim($code)));

        return DB::transaction(function () use ($u, $digest) {
            $fresh = User::query()->lockForUpdate()->find($u->id);
            $codes = $fresh->two_factor_recovery_codes ?? [];
            $idx = null;
            foreach ($codes as $i => $h) {
                if (hash_equals($h, $digest)) {
                    $idx = $i;
                }
            }
            if ($idx === null) {
                return false;
            }
            unset($codes[$idx]);                                      // single use
            $fresh->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
            $this->audit->log('2fa.recovery_used', $fresh);

            return true;
        });
    }

    private function newRecoveryCodes(): array
    {
        return array_map(fn () => implode('-', str_split(bin2hex(random_bytes(8)), 4)), range(1, 8));   // 64-bit codes xxxx-xxxx-xxxx-xxxx
    }

    private function digest(string $code): string
    {
        return hash('sha256', $code);        // high-entropy random codes -> fast hash is sufficient (no need for bcrypt)
    }
}

/*
SYSTEM DESIGN:
2FA lifecycle service: begin -> confirm -> (use) -> regenerate/disable. Recovery codes are stored only as SHA-256 digests; consumption is done under a row lock; TOTP step is consumed with a conditional UPDATE (compare-and-set) -> atomic replay protection.

DATA STRUCTURE:
Recovery set = list of 8 digests (set semantics, removal on use). Compare-and-set on two_factor_last_step (monotonic counter).
*/
