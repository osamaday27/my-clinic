<?php
namespace App\Livewire\Portal;

use App\Enums\AppointmentStatus as S;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\Prescription;
use App\Services\PortalService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.portal')]
class Home extends Component
{
    #[Computed]
    public function patient()
    {
        return app(PortalService::class)->patientOf(auth()->user());
    }

    #[Computed]
    public function upcoming()
    {
        return Appointment::query()->with(['doctor.user', 'clinic', 'branch'])->where('patient_id', $this->patient->id)
            ->whereIn('status', [S::Scheduled->value, S::Confirmed->value, S::CheckedIn->value])->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')->orderBy('start_time')->get();
    }

    #[Computed]
    public function past()
    {
        return Appointment::query()->with(['doctor.user', 'clinic'])->where('patient_id', $this->patient->id)
            ->whereIn('status', [S::Completed->value, S::Cancelled->value, S::NoShow->value])->latest('appointment_date')->limit(5)->get();
    }

    #[Computed]
    public function prescriptions()
    {
        return Prescription::query()->with(['doctor.user', 'items.medicine'])->where('patient_id', $this->patient->id)->latest()->limit(5)->get();
    }

    #[Computed]
    public function labs()
    {
        return LabRequest::query()->where('patient_id', $this->patient->id)->where('status', 'resulted')->latest()->limit(5)->get();
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()->where('patient_id', $this->patient->id)->latest()->limit(5)->get();
    }

    public function cancel(int $id, PortalService $svc): void
    {
        $svc->cancel(auth()->user(), $id);
        unset($this->upcoming, $this->past);
        $this->dispatch('toast', message: __('clinic.toast.portal_cancelled'));
    }

    public function render()
    {
        return view('livewire.portal.home');
    }
}

/*
SYSTEM DESIGN:
Patient dashboard. Every query is rooted at the authenticated account's own patient id; only FINAL lab results are listed; lists are bounded (limit).

DATA STRUCTURE:
Six bounded queries (upcoming / past / rx / labs / invoices) keyed by patient_id (indexed).
*/
