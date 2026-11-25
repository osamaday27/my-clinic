<?php
namespace App\Livewire\Pages;

use App\Models\Branch;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsPage extends Component
{
    #[Computed]
    public function branches()
    {
        return Branch::query()->withCount('clinics')->get();
    }

    #[Computed]
    public function staff()
    {
        return User::query()->with('branch')->orderBy('role')->orderBy('name')->get();
    }

    public function toggle(int $id): void
    {
        abort_unless(auth()->user()->role === \App\Enums\Role::Manager, 403);
        abort_if($id === auth()->id(), 422);            // cannot lock yourself out
        $u = User::findOrFail($id);
        $u->update(['is_active' => ! $u->is_active]);
        $this->dispatch('toast', message: __('clinic.toast.saved'));
    }

    public function render()
    {
        return view('pages.settings');
    }
}

/*
SYSTEM DESIGN:
Manager-only settings: branches overview + staff activation (soft access control; deactivated users are rejected by EnsureRole on the next request).

DATA STRUCTURE:
Two Collections; withCount aggregate avoids N+1.
*/
