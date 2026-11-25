<?php
namespace App\Livewire;

use App\Enums\AppointmentStatus as S;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentService;
use App\Services\BillingService;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class AppointmentsBoard extends Component
{
    public string $date = '';
    public string $doctorFilter = '';
    public string $statusFilter = '';
    public bool $pickDate = false;

    public ?int $rescheduleId = null;
    public string $newDate = '';
    public string $newTime = '';

    public function mount(bool $pickDate = false): void
    {
        $this->date = today()->toDateString();
        $this->pickDate = $pickDate;
    }

    #[On('appointment-saved')]
    public function refresh(): void
    {
        // re-render only
    }

    #[Computed]
    public function appointments()
    {
        $filters = ['doctor_id' => $this->doctorFilter ?: null, 'status' => $this->statusFilter ?: null];
        $user = auth()->user();
        if ($user->role === Role::Doctor) {
            $filters['doctor_id'] = $user->doctorId() ?? 0;      // a doctor can never widen this filter
        }

        return app(AppointmentService::class)->board(CurrentBranch::id(), $this->date, $filters);
    }

    #[Computed]
    public function doctors()
    {
        return Doctor::query()->with('user:id,name')->where('is_active', true)
            ->whereHas('clinic', fn ($q) => $q->where('branch_id', CurrentBranch::id()))->get();
    }

    /** Patient currently in consultation, otherwise the next one in the waiting room. */
    #[Computed]
    public function active(): ?Appointment
    {
        $all = $this->appointments;

        return $all->firstWhere('status', S::InProgress)
            ?? $all->where('status', S::CheckedIn)->sortBy('token_number')->first();
    }

    #[Computed]
    public function queue()
    {
        $activeId = $this->active?->id;

        return $this->appointments->where('status', S::CheckedIn)->where('id', '!=', $activeId)->sortBy('token_number')->values();
    }

    #[Computed]
    public function lastVisit(): ?string
    {
        if (! $this->active) {
            return null;
        }

        return Appointment::query()->where('patient_id', $this->active->patient_id)
            ->where('status', S::Completed->value)->where('id', '!=', $this->active->id)
            ->max('appointment_date');
    }

    private function find(int $id): Appointment
    {
        // Branch-scoped lookup: a tampered $id from another branch yields 404.
        return Appointment::query()->forBranch(CurrentBranch::id())->findOrFail($id);
    }

    public function checkIn(int $id, AppointmentService $svc): void
    {
        $a = $this->find($id);
        Gate::authorize('manage', $a);
        $svc->transition($a, S::CheckedIn);
        $this->toast(__('clinic.toast.checked_in'));
    }

    public function start(int $id, AppointmentService $svc): void
    {
        $a = $this->find($id);
        Gate::authorize('treat', $a);
        $svc->transition($a, S::InProgress);
    }

    public function complete(int $id, BillingService $billing): void
    {
        $a = $this->find($id);
        Gate::authorize('treat', $a);
        $invoice = $billing->completeVisit($a);
        $this->toast(__('clinic.toast.completed', ['no' => $invoice->number]));
    }

    public function cancel(int $id, AppointmentService $svc): void
    {
        $a = $this->find($id);
        Gate::authorize('manage', $a);
        $svc->transition($a, S::Cancelled, 'front-desk');
        $this->toast(__('clinic.toast.cancelled'));
    }

    public function openReschedule(int $id): void
    {
        $a = $this->find($id);
        Gate::authorize('manage', $a);
        $this->rescheduleId = $id;
        $this->newDate = $a->appointment_date->toDateString();
        $this->newTime = $a->time_label;
    }

    public function saveReschedule(AppointmentService $svc): void
    {
        $this->validate(
            ['newDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'], 'newTime' => ['required', 'date_format:H:i']],
            [], ['newDate' => __('clinic.attributes.appointment_date'), 'newTime' => __('clinic.attributes.start_time')],
        );
        $a = $this->find($this->rescheduleId);
        Gate::authorize('manage', $a);
        $svc->reschedule($a, $this->newDate, $this->newTime);
        $this->reset('rescheduleId', 'newDate', 'newTime');
        $this->toast(__('clinic.toast.rescheduled'));
    }

    private function toast(string $message): void
    {
        $this->dispatch('toast', message: $message);
    }

    public function render()
    {
        return view('livewire.appointments-board');
    }
}

/*
SYSTEM DESIGN:
Split-view controller (table + right-hand 'Next patient' card). Every mutating action: find() is branch-scoped -> Gate::authorize() (policy) -> service (FSM + transaction). Polls every 15s so the live queue stays current without WebSockets.

DATA STRUCTURE:
Computed collections: appointments (eager-loaded, ordered by start_time) -> derived views active / queue via in-memory filter + sortBy(token). Queue = priority list ordered by token_number (FIFO).
*/
