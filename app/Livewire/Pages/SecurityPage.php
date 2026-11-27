<?php
namespace App\Livewire\Pages;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SecurityPage extends Component
{
    public string $code = '';
    public string $password = '';
    public array $codes = [];
    public bool $showDisable = false;

    #[Computed]
    public function user(): User
    {
        return User::query()->findOrFail(auth()->id());
    }

    private function refresh(): void
    {
        unset($this->user);
        $this->reset('code', 'password');
    }

    private function throttle(): void
    {
        $key = 'security:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => __('clinic.errors.tf_throttle')]);
        }
        RateLimiter::hit($key, 60);
    }

    private function checkPassword(): void
    {
        if (! Hash::check($this->password, $this->user->password)) {
            throw ValidationException::withMessages(['password' => __('clinic.errors.bad_password')]);
        }
    }

    public function start(TwoFactorService $tf): void
    {
        abort_if($this->user->hasTwoFactor(), 422);
        $tf->begin($this->user);
        $this->refresh();
    }

    public function confirm(TwoFactorService $tf): void
    {
        $this->throttle();
        $this->validate(['code' => ['required', 'string', 'max:10']], [], ['code' => __('clinic.ui.tf_code')]);
        $codes = $tf->confirm($this->user, $this->code);
        if ($codes === null) {
            throw ValidationException::withMessages(['code' => __('clinic.errors.tf_invalid')]);
        }
        $this->codes = $codes;
        $this->refresh();
        $this->dispatch('toast', message: __('clinic.toast.tf_enabled'));
    }

    public function regenerate(TwoFactorService $tf): void
    {
        $this->throttle();
        $this->checkPassword();
        abort_unless($this->user->hasTwoFactor(), 422);
        $this->codes = $tf->regenerate($this->user);
        $this->refresh();
        $this->dispatch('toast', message: __('clinic.toast.tf_regen'));
    }

    public function disable(TwoFactorService $tf): void
    {
        $this->throttle();
        $this->checkPassword();
        if (! $tf->verifyAndConsume($this->user, $this->code)) {
            throw ValidationException::withMessages(['code' => __('clinic.errors.tf_invalid')]);
        }
        $tf->disable($this->user);
        $this->showDisable = false;
        $this->codes = [];
        $this->refresh();
        $this->dispatch('toast', message: __('clinic.toast.tf_disabled'));
    }

    public function render()
    {
        return view('pages.security');
    }
}

/*
SYSTEM DESIGN:
Self-service security page for every staff role. Sensitive operations (confirm, regenerate, disable) are rate-limited (5/min) and require the current password; disabling also requires a valid TOTP.

DATA STRUCTURE:
Computed user model re-read after every mutation (unset) to avoid stale state.
*/
