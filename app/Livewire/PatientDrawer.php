<?php
namespace App\Livewire;

use App\Models\Patient;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PatientDrawer extends Component
{
    public ?int $patientId = null;
    public string $tab = 'overview';

    #[On('open-patient')]
    public function open(int $id): void
    {
        $patient = Patient::query()->findOrFail($id);
        Gate::authorize('view', $patient);
        app(AuditService::class)->log('patient.viewed', $patient);       // access trail

        $this->patientId = $id;
        $this->tab = 'overview';
    }

    public function close(): void
    {
        $this->patientId = null;
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['overview', 'history', 'appointments', 'invoices'], true) ? $tab : 'overview';
    }

    #[Computed]
    public function patient(): ?Patient
    {
        if (! $this->patientId) {
            return null;
        }
        // $patientId is client-controlled (Livewire public prop) -> re-authorise on EVERY request.
        $p = Patient::query()->findOrFail($this->patientId);
        Gate::authorize('view', $p);

        return $p->load([
            'vitals' => fn ($q) => $q->latest()->limit(5),
            'records' => fn ($q) => $q->with('doctor.user')->latest()->limit(10),
            'prescriptions' => fn ($q) => $q->with(['doctor.user', 'items.medicine'])->latest()->limit(10),
            'appointments' => fn ($q) => $q->with('doctor.user')->latest('appointment_date')->limit(10),
            'invoices' => fn ($q) => $q->latest()->limit(10),
            'labRequests' => fn ($q) => $q->latest()->limit(10),
        ]);
    }

    public function render()
    {
        return view('livewire.patient-drawer');
    }
}

/*
SYSTEM DESIGN:
Right-side drawer with tabs (Overview & Vitals / History & Rx / Appointments / Invoices). Eager loads with per-relation LIMIT (no N+1, bounded payload) and re-authorises on every Livewire round-trip because public properties can be tampered with in the browser.

DATA STRUCTURE:
Aggregate load: Patient + 5 bounded relations = 6 queries. Tab = whitelisted string state machine.
*/
