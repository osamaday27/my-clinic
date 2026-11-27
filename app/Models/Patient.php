<?php
namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use Localized, SoftDeletes;

    protected $fillable = [
        'file_number', 'name_ar', 'name_en', 'phone', 'national_id', 'birth_date', 'gender',
        'allergies', 'chronic_conditions', 'insurance_company', 'insurance_company_id', 'insurance_number',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            // Field-level encryption at rest (AES-256-CBC via APP_KEY) - Rule 40
            'national_id' => 'encrypted',
            'allergies' => 'encrypted',
            'chronic_conditions' => 'encrypted',
        ];
    }

    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function records(): HasMany { return $this->hasMany(MedicalRecord::class); }
    public function vitals(): HasMany { return $this->hasMany(Vital::class); }
    public function prescriptions(): HasMany { return $this->hasMany(Prescription::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function labRequests(): HasMany { return $this->hasMany(LabRequest::class); }
    public function portalUser(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(User::class, 'patient_id'); }
    public function insuranceCompany(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(InsuranceCompany::class); }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function hasAlerts(): bool
    {
        return filled($this->allergies) || filled($this->chronic_conditions);
    }

    /** "Doctor sees only his own patients" - Rule 39. Enforced at query level (not just in views). */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return match ($user->role) {
            Role::Doctor => $q->whereHas('appointments', fn ($a) => $a->where('doctor_id', $user->doctorId() ?? 0)),
            Role::Patient => $q->whereRaw('1 = 0'),
            default => $q,
        };
    }

    /** Safe LIKE search (wildcards escaped) over indexed, NON-encrypted columns. */
    public function scopeSearch(Builder $q, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $q;
        }
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $q->where(fn ($w) => $w
            ->where('name_ar', 'like', $like)
            ->orWhere('name_en', 'like', $like)
            ->orWhere('phone', 'like', $like)
            ->orWhere('file_number', 'like', $like));
    }
}

/*
SYSTEM DESIGN:
Aggregate root of the EMR. Identity data is isolated from visits/diagnoses (normalisation). Authorization is applied BOTH in query scope (visibleTo) and in PatientPolicy (defence in depth against IDOR).

DATA STRUCTURE:
Encrypted columns cannot be indexed/searched, therefore search runs only on name/phone/file_number (B-tree indexed). 1-N relations to appointments, records, vitals, prescriptions, invoices.
*/
