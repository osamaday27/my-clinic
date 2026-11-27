<?php
namespace App\Livewire\Pages;

use App\Models\Patient;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PatientsPage extends Component
{
    use WithPagination;

    public string $search = '';

    #[On('patient-saved')]
    public function refresh(): void
    {
        unset($this->patients);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function patients()
    {
        return Patient::query()->visibleTo(auth()->user())->search($this->search)
            ->orderBy('name_ar')->simplePaginate(15);       // Rule 15: pagination, no ->get() of the whole table
    }

    public function render()
    {
        return view('pages.patients');
    }
}

/*
SYSTEM DESIGN:
Patient directory with server-side pagination and the same visibleTo() scope as global search.

DATA STRUCTURE:
simplePaginate(15): LIMIT/OFFSET without COUNT(*) -> cheaper on large tables.
*/
