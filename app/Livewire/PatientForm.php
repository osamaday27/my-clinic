<?php
namespace App\Livewire;

use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PatientForm extends Component
{
    public bool $open = false;
    public ?int $patientId = null;

    public string $name_ar = '';
    public string $name_en = '';
    public string $phone = '';
    public string $national_id = '';
    public string $birth_date = '';
    public string $gender = '';
    public string $allergies = '';
    public string $chronic_conditions = '';
    public string $insurance_company_id = '';
    public string $insurance_number = '';

    #[On('open-patient-form')]
    public function show(?int $id = null): void
    {
        $this->reset();
        if ($id) {
            $p = Patient::query()->findOrFail($id);
            Gate::authorize('update', $p);
            $this->patientId = $id;
            $this->fill([
                'name_ar' => (string) $p->name_ar, 'name_en' => (string) $p->name_en, 'phone' => (string) $p->phone,
                'national_id' => (string) $p->national_id, 'birth_date' => (string) $p->birth_date?->toDateString(), 'gender' => (string) $p->gender,
                'allergies' => (string) $p->allergies, 'chronic_conditions' => (string) $p->chronic_conditions,
                'insurance_company_id' => (string) $p->insurance_company_id, 'insurance_number' => (string) $p->insurance_number,
            ]);
        } else {
            Gate::authorize('create', Patient::class);
        }
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompany::query()->where('is_active', true)->orderBy('name_en')->get();
    }

    #[Computed]
    public function duplicatePhone(): bool
    {
        return strlen($this->phone) >= 7 && app(PatientService::class)->phoneExists($this->phone, $this->patientId);
    }

    public function save(PatientService $svc): void
    {
        $data = $this->validate([
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'national_id' => ['nullable', 'string', 'regex:/^[0-9]{8,20}$/'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['nullable', 'in:male,female'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
            'insurance_company_id' => ['nullable', 'integer', 'exists:insurance_companies,id'],
            'insurance_number' => ['nullable', 'string', 'max:60'],
        ], [], [
            'name_ar' => __('clinic.ui.name_ar'), 'name_en' => __('clinic.ui.name_en'), 'phone' => __('clinic.ui.phone'), 'national_id' => __('clinic.ui.national_id'),
            'birth_date' => __('clinic.ui.birth_date'), 'gender' => __('clinic.ui.gender'), 'allergies' => __('clinic.ui.allergy'),
            'chronic_conditions' => __('clinic.ui.chronic'), 'insurance_company_id' => __('clinic.ui.insurance'), 'insurance_number' => __('clinic.ui.ins_number'),
        ]);

        if ($this->patientId) {
            $p = Patient::query()->findOrFail($this->patientId);
            Gate::authorize('update', $p);
            $svc->update($p, $data);
        } else {
            Gate::authorize('create', Patient::class);
            $p = $svc->register($data);
        }

        $this->open = false;
        $this->dispatch('patient-saved');
        $this->dispatch('toast', message: __('clinic.toast.patient_saved'));
        $this->dispatch('open-patient', id: $p->id);
    }

    public function render()
    {
        return view('livewire.patient-form');
    }
}

/*
SYSTEM DESIGN:
Patient registration/edit modal. Authorization is re-checked on save (never trust the client-side patientId). Duplicate phone is a soft warning (families legitimately share numbers), not a hard block.

DATA STRUCTURE:
Typed string properties (form state) -> validated array -> PatientService whitelist.
*/
