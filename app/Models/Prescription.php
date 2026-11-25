<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use SoftDeletes;

    protected $fillable = ['patient_id', 'doctor_id', 'appointment_id', 'language', 'notes'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function items(): HasMany { return $this->hasMany(PrescriptionItem::class); }
}

/*
SYSTEM DESIGN:
Prescription header (master). Rendered in the patient's language (column `language`).

DATA STRUCTURE:
Master-detail: Prescription 1-N PrescriptionItem.
*/
