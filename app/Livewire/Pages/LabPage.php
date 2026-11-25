<?php
namespace App\Livewire\Pages;

use App\Enums\Role;
use App\Models\LabRequest;
use App\Services\LabService;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LabPage extends Component
{
    use WithFileUploads, WithPagination;

    public string $status = '';
    public ?int $resultId = null;
    public string $result = '';
    public $file = null;

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        $u = auth()->user();

        return LabRequest::query()->with(['patient', 'doctor.user'])
            ->whereHas('appointment', fn ($q) => $q->where('branch_id', CurrentBranch::id()))
            ->when($u->role === Role::Doctor, fn ($q) => $q->where('doctor_id', $u->doctorId() ?? 0))
            ->when($this->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->simplePaginate(12);
    }

    private function find(int $id): LabRequest
    {
        return LabRequest::query()->whereHas('appointment', fn ($q) => $q->where('branch_id', CurrentBranch::id()))->findOrFail($id);
    }

    public function openResult(int $id): void
    {
        $lab = $this->find($id);
        Gate::authorize('result', $lab);
        $this->resultId = $id;
        $this->result = (string) $lab->result;
        $this->file = null;
    }

    public function saveResult(LabService $svc): void
    {
        $this->validate([
            'result' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'mimes:'.implode(',', config('clinic.lab.mimes')), 'max:'.config('clinic.lab.max_kb')],
        ], [], ['result' => __('clinic.ui.result'), 'file' => __('clinic.ui.attachment')]);

        $lab = $this->find($this->resultId);
        Gate::authorize('result', $lab);
        $svc->recordResult($lab, $this->result, $this->file, auth()->user());

        $this->reset('resultId', 'result', 'file');
        $this->dispatch('toast', message: __('clinic.toast.lab_resulted'));
    }

    public function render()
    {
        return view('pages.lab');
    }
}

/*
SYSTEM DESIGN:
Lab & radiology worklist: branch-scoped, doctors see only their own orders; nurse/manager record results with optional attachment (type + size validated server-side; MIME sniffed from content by Laravel's `mimes` rule).

DATA STRUCTURE:
Paginated worklist; temporary upload handled by Livewire then moved to the private disk by LabService.
*/
