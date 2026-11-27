<?php
namespace App\Livewire\Portal;

use App\Models\Doctor;
use App\Services\AppointmentService;
use App\Services\PortalService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.portal')]
class Book extends Component
{
    public ?int $doctor_id = null;
    public string $appointment_date = '';
    public string $start_time = '';
    public string $chief_complaint = '';

    public function mount(): void
    {
        $this->appointment_date = today()->toDateString();
    }

    public function updatedDoctorId(): void { $this->start_time = ''; }
    public function updatedAppointmentDate(): void { $this->start_time = ''; }

    #[Computed]
    public function doctors()
    {
        return Doctor::query()->with(['user:id,name', 'clinic.branch'])->where('is_active', true)->get();
    }

    #[Computed]
    public function slots(): array
    {
        $doctor = $this->doctor_id ? $this->doctors->firstWhere('id', $this->doctor_id) : null;
        if (! $doctor || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->appointment_date) || $this->appointment_date < today()->toDateString()) {
            return [];
        }

        return app(AppointmentService::class)->slotMap($doctor, $this->appointment_date);
    }

    public function save(PortalService $svc)
    {
        $data = $this->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(60)->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
        ], __('clinic.validation'), __('clinic.attributes'));

        $svc->book(auth()->user(), $data);
        session()->flash('status', __('clinic.toast.portal_booked'));

        return $this->redirectRoute('portal');
    }

    public function render()
    {
        return view('livewire.portal.book');
    }
}

/*
SYSTEM DESIGN:
Self-service booking. The browser only chooses doctor/date/time; the patient is fixed server-side. Booking window limited to 60 days; the service re-validates hours, past times, conflicts and per-patient limits.

DATA STRUCTURE:
Hash-map of slots reused from AppointmentService::slotMap.
*/
