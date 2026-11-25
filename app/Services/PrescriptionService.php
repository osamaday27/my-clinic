<?php
namespace App\Services;

use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionService
{
    public function __construct(private AuditService $audit) {}

    /** Cached catalogue keyed by id (Rule 19). */
    public function catalogue(): Collection
    {
        return Cache::remember('medicines:active', 3600, fn () => Medicine::query()
            ->where('is_active', true)->orderBy('name_en')->get()->keyBy('id'));
    }

    /** Returns medicines that clash with the patient's recorded allergies. */
    public function allergyConflicts(Patient $patient, array $medicineIds): Collection
    {
        $allergens = collect(preg_split('/[,;،\n]+/u', mb_strtolower((string) $patient->allergies)))
            ->map(fn ($s) => trim($s))->filter()->values();
        if ($allergens->isEmpty()) {
            return collect();
        }

        return $this->catalogue()->only($medicineIds)->filter(function (Medicine $m) use ($allergens) {
            $names = collect([$m->name_en, $m->name_ar, $m->generic_name])->filter()->map(fn ($n) => mb_strtolower($n));

            return $allergens->contains(fn ($al) => $names->contains(fn ($n) => str_contains($n, $al) || str_contains($al, $n)));
        })->values();
    }

    public function issue(Doctor $doctor, Patient $patient, array $items, ?int $appointmentId, string $lang = 'ar', ?string $notes = null, bool $allergyAcknowledged = false): Prescription
    {
        $conflicts = $this->allergyConflicts($patient, array_column($items, 'medicine_id'));
        if ($conflicts->isNotEmpty() && ! $allergyAcknowledged) {
            throw ValidationException::withMessages(['items' => __('clinic.errors.allergy', ['names' => $conflicts->pluck('name')->join(', ')])]);
        }

        return DB::transaction(function () use ($doctor, $patient, $items, $appointmentId, $lang, $notes) {
            $rx = Prescription::create([
                'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
                'appointment_id' => $appointmentId, 'language' => $lang, 'notes' => $notes,
            ]);
            $rx->items()->createMany($items);
            $this->audit->log('prescription.issued', $rx);

            return $rx;
        });
    }
}

/*
SYSTEM DESIGN:
Prescription issuing with a safety net: drug-allergy check (Rule 33) and doctor acknowledgement required to override. Catalogue cached in Redis/Memcached when CACHE_STORE is configured.

DATA STRUCTURE:
Cache key 'medicines:active' -> Collection keyed by id (hash-map). Allergy match = set intersection of tokenised allergens vs drug names/generic.
*/
