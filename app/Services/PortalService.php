<?php
namespace App\Services;

use App\Enums\AppointmentStatus as S;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PortalService
{
    public function __construct(private AppointmentService $appointments, private AuditService $audit) {}

    /** The ONLY way the portal learns which patient is acting: from the authenticated account. */
    public function patientOf(User $u): Patient
    {
        abort_unless($u->role === Role::Patient && $u->patient_id, 403);

        return Patient::query()->findOrFail($u->patient_id);
    }

    public function book(User $u, array $data): Appointment
    {
        $patient = $this->patientOf($u);

        $active = Appointment::query()->where('patient_id', $patient->id)
            ->whereIn('status', [S::Scheduled->value, S::Confirmed->value])->whereDate('appointment_date', '>=', today())->count();
        $today = Appointment::query()->withTrashed()->where('patient_id', $patient->id)->where('created_by', $u->id)->whereDate('created_at', today())->count();
        if ($active >= config('clinic.portal.max_active') || $today >= config('clinic.portal.max_per_day')) {
            throw ValidationException::withMessages(['action' => __('clinic.errors.portal_limit')]);      // anti-hoarding / spam
        }

        $a = $this->appointments->book([
            'patient_id' => $patient->id,                      // forced - any patient_id from the browser is ignored
            'doctor_id' => $data['doctor_id'], 'appointment_date' => $data['appointment_date'], 'start_time' => $data['start_time'],
            'visit_type' => 'consultation', 'chief_complaint' => $data['chief_complaint'] ?? null,
        ], $u);
        $this->audit->log('portal.booked', $a);

        return $a;
    }

    public function cancel(User $u, int $appointmentId): Appointment
    {
        $patient = $this->patientOf($u);
        $a = Appointment::query()->where('patient_id', $patient->id)->findOrFail($appointmentId);      // someone else's id -> 404

        $start = Carbon::parse($a->appointment_date->toDateString().' '.$a->start_time);
        if (! in_array($a->status, [S::Scheduled, S::Confirmed], true) || $start->copy()->subHours(config('clinic.portal.cancel_before_hours'))->isPast()) {
            throw ValidationException::withMessages(['action' => __('clinic.errors.portal_too_late')]);
        }
        $this->appointments->transition($a, S::Cancelled, 'patient');
        $this->audit->log('portal.cancelled', $a);

        return $a;
    }
}

/*
SYSTEM DESIGN:
Self-service use-cases with ownership enforced by construction (queries always start from the account's own patient_id). Business limits: max active bookings, max per day, cancellation cut-off.

DATA STRUCTURE:
Counters via COUNT(*) on indexed columns; time arithmetic with Carbon (start - N hours vs now).
*/
