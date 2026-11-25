<?php
namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\LabRequest;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicalService
{
    public function __construct(private AuditService $audit) {}

    public function recordVitals(Appointment $a, array $data, User $by): Vital
    {
        $this->ensureOpen($a);
        $clean = array_filter(Arr::only($data, ['bp_systolic', 'bp_diastolic', 'heart_rate', 'temperature', 'spo2', 'weight']), fn ($v) => $v !== '' && $v !== null);
        if (! $clean) {
            throw ValidationException::withMessages(['v' => __('clinic.errors.vitals_empty')]);
        }

        return DB::transaction(function () use ($a, $clean, $by) {
            $v = Vital::create($clean + ['patient_id' => $a->patient_id, 'appointment_id' => $a->id, 'recorded_by' => $by->id]);
            $this->audit->log('vitals.recorded', $v);

            return $v;
        });
    }

    public function diagnose(Appointment $a, array $data): MedicalRecord
    {
        $this->ensureOpen($a);

        return DB::transaction(function () use ($a, $data) {
            $r = MedicalRecord::create([
                'patient_id' => $a->patient_id, 'doctor_id' => $a->doctor_id, 'appointment_id' => $a->id,
                'icd_code' => $data['icd_code'] ?: null, 'diagnosis' => $data['diagnosis'], 'notes' => $data['notes'] ?: null,
            ]);
            $this->audit->log('diagnosis.recorded', $r);

            return $r;
        });
    }

    public function requestLab(Appointment $a, string $type, string $test): LabRequest
    {
        $this->ensureOpen($a);

        return DB::transaction(function () use ($a, $type, $test) {
            $l = LabRequest::create([
                'patient_id' => $a->patient_id, 'doctor_id' => $a->doctor_id, 'appointment_id' => $a->id,
                'type' => $type, 'test_name' => $test, 'status' => 'requested',
            ]);
            $this->audit->log('lab.requested', $l);

            return $l;
        });
    }

    /** Charting is allowed only while the patient is in the clinic. */
    private function ensureOpen(Appointment $a): void
    {
        if (! in_array($a->status, [AppointmentStatus::CheckedIn, AppointmentStatus::InProgress], true)) {
            throw ValidationException::withMessages(['action' => __('clinic.errors.visit_closed')]);
        }
    }
}

/*
SYSTEM DESIGN:
Application service for the clinical record (EMR write side). Every write: guard on visit state -> DB::transaction -> audit entry. Only whitelisted vital columns are accepted (Arr::only) so mass-assignment of foreign keys is impossible.

DATA STRUCTURE:
Whitelist map of allowed columns; empty-reading rejection via array_filter. Time-series append to vitals; append-only medical_records.
*/
