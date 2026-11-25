<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalRecord extends Model
{
    use SoftDeletes;

    protected $fillable = ['patient_id', 'doctor_id', 'appointment_id', 'icd_code', 'diagnosis', 'notes'];

    protected $casts = ['notes' => 'encrypted'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
}

/*
SYSTEM DESIGN:
Diagnosis entry (immutable history: soft delete only). Clinical notes encrypted at rest.

DATA STRUCTURE:
Append-mostly list per patient, paginated/ordered by created_at DESC.
*/
