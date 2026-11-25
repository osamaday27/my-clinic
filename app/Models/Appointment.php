<?php
namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\VisitType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'clinic_id', 'branch_id', 'appointment_date', 'start_time', 'end_time',
        'visit_type', 'status', 'token_number', 'chief_complaint', 'cancel_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'status' => AppointmentStatus::class,
            'visit_type' => VisitType::class,
            'reminder_sent_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function clinic(): BelongsTo { return $this->belongsTo(Clinic::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public function scopeOnDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('appointment_date', $date);
    }

    public function scopeForBranch(Builder $q, ?int $branchId): Builder
    {
        return $branchId ? $q->where('branch_id', $branchId) : $q;
    }

    public function getTimeLabelAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }
}

/*
SYSTEM DESIGN:
Central scheduling entity. status is an enum-FSM; start/end persisted so overlap detection is a pure SQL range query.

DATA STRUCTURE:
Row = interval [start_time, end_time) on a (doctor, date) timeline; token_number = per-branch per-day counter (queue).
*/
