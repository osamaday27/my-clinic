<?php
namespace App\Livewire;

use App\Models\Appointment;
use App\Models\LabRequest;
use App\Models\MedicalRecord;
use App\Models\Vital;
use App\Services\ClinicalService;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class VisitChart extends Component
{
    public ?int $appointmentId = null;
    public string $tab = 'vitals';

    public array $v = ['bp_systolic' => '', 'bp_diastolic' => '', 'heart_rate' => '', 'temperature' => '', 'spo2' => '', 'weight' => ''];
    public string $diagnosis = '';
    public string $icd_code = '';
    public string $notes = '';
    public string $lab_type = 'lab';
    public string $test_name = '';

    #[On('open-visit-chart')]
    public function show(int $appointmentId): void
    {
        $a = Appointment::query()->forBranch(CurrentBranch::id())->findOrFail($appointmentId);
        Gate::authorize('chart', $a);
        $this->reset();
        $this->appointmentId = $appointmentId;
        $this->tab = auth()->user()->can('treat', $a) ? 'diagnosis' : 'vitals';
    }

    public function close(): void
    {
        $this->reset();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['vitals', 'diagnosis', 'lab'], true) ? $tab : 'vitals';
    }

    /** Re-authorised on EVERY round-trip (appointmentId is client-controlled). */
    #[Computed]
    public function appointment(): ?Appointment
    {
        if (! $this->appointmentId) {
            return null;
        }
        $a = Appointment::query()->forBranch(CurrentBranch::id())->with(['patient', 'doctor.user'])->findOrFail($this->appointmentId);
        Gate::authorize('chart', $a);

        return $a;
    }

    #[Computed]
    public function recentVitals()
    {
        return Vital::query()->where('patient_id', $this->appointment->patient_id)->latest()->limit(3)->get();
    }

    #[Computed]
    public function records()
    {
        return MedicalRecord::query()->where('appointment_id', $this->appointmentId)->latest()->get();
    }

    #[Computed]
    public function labs()
    {
        return LabRequest::query()->where('appointment_id', $this->appointmentId)->latest()->get();
    }

    public function saveVitals(ClinicalService $svc): void
    {
        $a = $this->appointment;
        $this->validate([
            'v.bp_systolic' => ['nullable', 'integer', 'between:50,260'],
            'v.bp_diastolic' => ['nullable', 'integer', 'between:30,160'],
            'v.heart_rate' => ['nullable', 'integer', 'between:20,250'],
            'v.temperature' => ['nullable', 'numeric', 'between:30,45'],
            'v.spo2' => ['nullable', 'integer', 'between:50,100'],
            'v.weight' => ['nullable', 'numeric', 'between:1,400'],
        ], [], [
            'v.bp_systolic' => __('clinic.ui.systolic'), 'v.bp_diastolic' => __('clinic.ui.diastolic'), 'v.heart_rate' => __('clinic.ui.bpm'),
            'v.temperature' => __('clinic.ui.temp_c'), 'v.spo2' => 'SpO₂', 'v.weight' => __('clinic.ui.weight_kg'),
        ]);

        $svc->recordVitals($a, $this->v, auth()->user());
        $this->reset('v');
        unset($this->recentVitals);
        $this->dispatch('toast', message: __('clinic.toast.vitals_saved'));
    }

    public function saveDiagnosis(ClinicalService $svc): void
    {
        $a = $this->appointment;
        Gate::authorize('treat', $a);
        $this->validate([
            'diagnosis' => ['required', 'string', 'max:1000'],
            'icd_code' => ['nullable', 'string', 'max:12', 'regex:/^[A-Z][0-9]{2}(\.[0-9A-Z]{1,4})?$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['diagnosis' => __('clinic.ui.diagnosis'), 'icd_code' => __('clinic.ui.icd'), 'notes' => __('clinic.ui.clin_notes')]);

        $svc->diagnose($a, ['diagnosis' => $this->diagnosis, 'icd_code' => $this->icd_code, 'notes' => $this->notes]);
        $this->reset('diagnosis', 'icd_code', 'notes');
        unset($this->records);
        $this->dispatch('toast', message: __('clinic.toast.diagnosis_saved'));
    }

    public function requestLab(ClinicalService $svc): void
    {
        $a = $this->appointment;
        Gate::authorize('treat', $a);
        $this->validate([
            'lab_type' => ['required', 'in:lab,radiology'],
            'test_name' => ['required', 'string', 'max:150'],
        ], [], ['lab_type' => __('clinic.ui.type'), 'test_name' => __('clinic.ui.test_name')]);

        $svc->requestLab($a, $this->lab_type, $this->test_name);
        $this->reset('test_name');
        unset($this->labs);
        $this->dispatch('toast', message: __('clinic.toast.lab_requested'));
    }

    public function render()
    {
        return view('livewire.visit-chart');
    }
}

/*
SYSTEM DESIGN:
Visit chart modal (EMR write side). Nurse: vitals. Doctor: vitals + diagnosis (ICD-10 validated) + lab/radiology orders. Authorization layers: branch-scoped lookup -> Policy 'chart' (every request) -> Policy 'treat' for clinical writes -> ClinicalService state guard.

DATA STRUCTURE:
Component state = flat typed props + one assoc array (v). Computed properties are invalidated with unset() after writes so lists refresh without extra queries.
*/
