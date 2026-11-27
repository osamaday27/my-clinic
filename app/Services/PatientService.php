<?php
namespace App\Services;

use App\Models\InsuranceCompany;
use App\Models\Patient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientService
{
    private const FIELDS = ['name_ar', 'name_en', 'phone', 'national_id', 'birth_date', 'gender', 'allergies', 'chronic_conditions', 'insurance_company_id', 'insurance_number'];

    public function __construct(private AuditService $audit) {}

    public function register(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $p = Patient::create($this->clean($data) + ['file_number' => 'TMP-'.Str::uuid()]);
            $p->update(['file_number' => 'P-'.str_pad((string) $p->id, 5, '0', STR_PAD_LEFT)]);   // sequential, race-free (uses the PK)
            $this->audit->log('patient.created', $p);

            return $p;
        });
    }

    public function update(Patient $p, array $data): Patient
    {
        return DB::transaction(function () use ($p, $data) {
            $p->update($this->clean($data));
            $this->audit->log('patient.updated', $p);

            return $p;
        });
    }

    /** Whitelist + normalise: '' -> null, and keep the denormalised insurer name in sync for display/search. */
    private function clean(array $data): array
    {
        $d = array_map(fn ($v) => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v, Arr::only($data, self::FIELDS));
        $d['insurance_company'] = ! empty($d['insurance_company_id']) ? InsuranceCompany::query()->find($d['insurance_company_id'])?->name_en : null;

        return $d;
    }

    public function phoneExists(string $phone, ?int $ignoreId = null): bool
    {
        return Patient::query()->where('phone', $phone)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
    }
}

/*
SYSTEM DESIGN:
Registration use-case: whitelist of writable fields (no mass-assignment of file_number), sequential file number derived from the primary key inside the transaction, audit trail on create/update.

DATA STRUCTURE:
Key-whitelist (Arr::only) + normalisation map. File number = 'P-' + zero-padded auto-increment id.
*/
