<?php
namespace App\Livewire;

use App\Enums\VisitType;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AppointmentService;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NewAppointment extends Component
{
    public bool $open = false;
    public string $patientQuery = '';
    public ?int $patient_id = null;
    public ?int $doctor_id = null;
    public string $appointment_date = '';
    public string $start_time = '';
    public string $visit_type = 'consultation';
    public string $chief_complaint = '';

    #[On('open-new-appointment')]
    public function show(?int $patientId = null): void
    {
        Gate::authorize('create', \App\Models\Appointment::class);
        $this->reset();
        $this->appointment_date = today()->toDateString();
        $this->patient_id = $patientId;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function updatedDoctorId(): void { $this->start_time = ''; }
    public function updatedAppointmentDate(): void { $this->start_time = ''; }

    public function pick(int $id): void
    {
        $this->patient_id = $id;
        $this->patientQuery = '';
    }

    #[Computed]
    public function patientResults()
    {
        if (mb_strlen(trim($this->patientQuery)) < 2) {
            return collect();
        }

        return Patient::query()->visibleTo(auth()->user())->search($this->patientQuery)->limit(5)->get();
    }

    #[Computed]
    public function patient(): ?Patient
    {
        return $this->patient_id ? Patient::query()->visibleTo(auth()->user())->find($this->patient_id) : null;
    }

    #[Computed]
    public function doctors()
    {
        return Doctor::query()->with(['user:id,name', 'clinic'])->where('is_active', true)
            ->whereHas('clinic', fn ($q) => $q->where('branch_id', CurrentBranch::id()))->get();
    }

    #[Computed]
    public function selectedDoctor(): ?Doctor
    {
        return $this->doctor_id ? $this->doctors->firstWhere('id', $this->doctor_id) : null;
    }

    /** ['09:00' => true|false, ...] */
    #[Computed]
    public function slots(): array
    {
        if (! $this->selectedDoctor || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->appointment_date)) {
            return [];
        }

        return app(AppointmentService::class)->slotMap($this->selectedDoctor, $this->appointment_date);
    }

    public function visitTypes(): array
    {
        return VisitType::cases();
    }

    public function save(AppointmentService $svc): void
    {
        Gate::authorize('create', \App\Models\Appointment::class);
        $req = new StoreAppointmentRequest();
        $data = $this->validate($req->rules(), $req->messages(), $req->attributes());

        $svc->book($data, auth()->user());

        $this->open = false;
        $this->dispatch('appointment-saved');
        $this->dispatch('toast', message: __('clinic.toast.booked'));
    }

    public function render()
    {
        return view('livewire.new-appointment');
    }
}

/*
SYSTEM DESIGN:
Booking modal. Rules/messages are reused from StoreAppointmentRequest (single source of truth). Slot picker is rendered from AppointmentService::slotMap; the SERVER re-checks conflicts inside a locked transaction on save (client state is never trusted).

DATA STRUCTURE:
slots(): hash-map 'HH:MM' => free?. Patient lookup uses visibleTo() so a receptionist/doctor can only pick allowed patients.
*/
