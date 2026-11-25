<?php
namespace App\Livewire;

use App\Models\Patient;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    #[Computed]
    public function results()
    {
        if (mb_strlen(trim($this->query)) < 2) {
            return collect();
        }

        return Patient::query()
            ->visibleTo(auth()->user())          // doctors only find their own patients
            ->search($this->query)
            ->orderBy('name_ar')
            ->limit(6)                           // bounded result set
            ->get(['id', 'file_number', 'name_ar', 'name_en', 'phone']);
    }

    public function open(int $id): void
    {
        $this->dispatch('open-patient', id: $id);
        $this->reset('query');
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}

/*
SYSTEM DESIGN:
Instant search (debounced 300ms) by name / phone / file number. Query is parameterised (Eloquent) and LIKE wildcards are escaped (scopeSearch) -> no SQLi / wildcard abuse.

DATA STRUCTURE:
Top-K (K=6) LIMIT over B-tree indexed columns. Result = Collection<Patient>.
*/
