<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    protected $fillable = ['patient_id', 'appointment_id', 'recorded_by', 'bp_systolic', 'bp_diastolic', 'heart_rate', 'temperature', 'spo2', 'weight'];

    protected $casts = ['temperature' => 'decimal:1', 'weight' => 'decimal:1'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }

    public function getBloodPressureAttribute(): ?string
    {
        return $this->bp_systolic ? "{$this->bp_systolic}/{$this->bp_diastolic}" : null;
    }
}

/*
SYSTEM DESIGN:
Vital-sign snapshots (time series) shown with tabular numerals in the patient drawer.

DATA STRUCTURE:
Time-series rows keyed by (patient_id, created_at).
*/
