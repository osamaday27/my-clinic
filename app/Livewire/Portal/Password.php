<?php
namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.portal')]
class Password extends Component
{
    public string $current = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function save()
    {
        $this->validate(
            ['current' => ['required', 'string'], 'password' => ['required', 'confirmed', 'different:current', PasswordRule::defaults()]],
            [], ['current' => __('clinic.ui.cur_password'), 'password' => __('clinic.ui.new_password')],
        );

        $user = auth()->user();
        if (! Hash::check($this->current, $user->password)) {
            throw ValidationException::withMessages(['current' => __('clinic.errors.pw_current')]);
        }

        $user->forceFill(['password' => $this->password, 'must_change_password' => false])->save();
        request()->session()->regenerate();
        session()->flash('status', __('clinic.toast.pw_changed'));

        return $this->redirectRoute('portal');
    }

    public function render()
    {
        return view('livewire.portal.password');
    }
}

/*
SYSTEM DESIGN:
Password change with the project-wide strong policy (min 10, mixed case, numbers). Requires the current/temporary password; session id is regenerated afterwards.

DATA STRUCTURE:

*/
