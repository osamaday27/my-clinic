<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabRequest extends Model
{
    use SoftDeletes;

    protected $fillable = ['patient_id', 'doctor_id', 'appointment_id', 'type', 'test_name', 'status', 'result', 'attachment_path'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
}

/*
SYSTEM DESIGN:
Lab / radiology order; attachment_path points to the PRIVATE disk (never public/), served only through an authorised controller.

DATA STRUCTURE:
Status queue: requested -> in_progress -> resulted.
*/
