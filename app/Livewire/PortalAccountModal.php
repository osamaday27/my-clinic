<?php
namespace App\Livewire;

use App\Models\Patient;
use App\Models\User;
use App\Services\PortalAccountService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PortalAccountModal extends Component
{
    public bool $open = false;
    public ?int $patientId = null;
    public string $email = '';
    public ?string $tempPassword = null;

    #[On('open-portal-account')]
    public function show(int $id): void
    {
        Gate::authorize('managePortal', Patient::query()->findOrFail($id));
        $this->reset();
        $this->patientId = $id;
        $this->open = true;
    }

    public function close(): void
    {
        $this->reset();                      // also wipes the temporary password from component state
    }

    private function patient(): Patient
    {
        $p = Patient::query()->findOrFail($this->patientId);
        Gate::authorize('managePortal', $p);

        return $p;
    }

    #[Computed]
    public function account(): ?User
    {
        return $this->patientId ? app(PortalAccountService::class)->accountOf($this->patient()) : null;
    }

    public function create(PortalAccountService $svc): void
    {
        $this->validate(['email' => ['required', 'email', 'max:190', 'unique:users,email']], [], ['email' => __('clinic.ui.portal_email')]);
        [, $this->tempPassword] = $svc->create($this->patient(), $this->email);
        unset($this->account);
        $this->dispatch('toast', message: __('clinic.toast.account_created'));
    }

    public function resetPassword(PortalAccountService $svc): void
    {
        abort_unless($this->account, 404);
        $this->tempPassword = $svc->resetPassword($this->account);
        $this->dispatch('toast', message: __('clinic.toast.account_reset'));
    }

    public function toggle(PortalAccountService $svc): void
    {
        abort_unless($this->account, 404);
        $svc->setActive($this->account, ! $this->account->is_active);
        unset($this->account);
        $this->dispatch('toast', message: __('clinic.toast.account_toggled'));
    }

    public function render()
    {
        return view('livewire.portal-account-modal');
    }
}

/*
SYSTEM DESIGN:
Front-desk tool to provision / reset / disable a patient's portal login. Authorised by PatientPolicy::managePortal on every call; the one-time temporary password lives only in component state and is cleared on close.

DATA STRUCTURE:
Unique email + unique patient_id guarantee 1 account per file.
*/
