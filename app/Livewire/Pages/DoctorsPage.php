<?php
namespace App\Livewire\Pages;

use App\Enums\Role;
use App\Models\Doctor;
use App\Support\CurrentBranch;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DoctorsPage extends Component
{
    #[Computed]
    public function doctors()
    {
        return Doctor::query()->with(['user:id,name,phone', 'clinic'])
            ->whereHas('clinic', fn ($q) => $q->where('branch_id', CurrentBranch::id()))
            ->withCount(['appointments as today_count' => fn ($q) => $q->whereDate('appointment_date', today())])
            ->get();
    }

    public function toggle(int $id): void
    {
        abort_unless(auth()->user()->role === Role::Manager, 403);
        $d = Doctor::query()->whereHas('clinic', fn ($q) => $q->where('branch_id', CurrentBranch::id()))->findOrFail($id);
        $d->update(['is_active' => ! $d->is_active]);
        $this->dispatch('toast', message: __('clinic.toast.saved'));
    }

    public function render()
    {
        return view('pages.doctors');
    }
}

/*
SYSTEM DESIGN:
Doctors & staff list with activate/deactivate switch (Rule 31). Deactivated doctors disappear from booking (StoreAppointmentRequest checks is_active).

DATA STRUCTURE:
withCount subquery = today's load per doctor in 1 query.
*/
