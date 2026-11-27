<?php
namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /**
     * Hash-map of every slot of the day: ['09:00' => true(free) | false(taken/past)].
     * One SQL query, then in-memory interval checks.
     */
    public function slotMap(Doctor $doctor, string $date, ?int $ignoreId = null): array
    {
        $hours = $doctor->working_hours ?: config('clinic.working_hours');
        $step = $doctor->slot_minutes ?: config('clinic.default_slot_minutes');
        $cursor = Carbon::parse("$date {$hours['start']}");
        $limit = Carbon::parse("$date {$hours['end']}");

        $booked = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', AppointmentStatus::blocking())
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get(['start_time', 'end_time'])
            ->map(fn ($a) => [substr($a->start_time, 0, 5), substr($a->end_time, 0, 5)]);

        $map = [];
        while ($cursor->copy()->addMinutes($step)->lte($limit)) {
            $start = $cursor->format('H:i');
            $end = $cursor->copy()->addMinutes($step)->format('H:i');
            $overlap = $booked->contains(fn ($b) => $b[0] < $end && $b[1] > $start);   // interval overlap test
            $past = $cursor->isPast();
            $map[$start] = ! $overlap && ! $past;
            $cursor->addMinutes($step);
        }

        return $map;
    }

    public function hasConflict(int $doctorId, string $date, string $start, string $end, ?int $ignoreId = null): bool
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', AppointmentStatus::blocking())
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function book(array $data, User $by): Appointment
    {
        return DB::transaction(function () use ($data, $by) {
            // Row lock on the doctor serialises concurrent bookings -> no double-booking race.
            $doctor = Doctor::query()->lockForUpdate()->with('clinic')->findOrFail($data['doctor_id']);
            if (! $doctor->is_active) {
                throw ValidationException::withMessages(['doctor_id' => __('clinic.errors.doctor_inactive')]);
            }

            [$start, $end] = $this->window($doctor, $data['start_time']);
            $this->assertBookable($doctor, $data['appointment_date'], $start, $end);
            $this->guard($doctor->id, $data['appointment_date'], $start, $end);

            return Appointment::create([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $doctor->id,
                'clinic_id' => $doctor->clinic_id,
                'branch_id' => $doctor->clinic->branch_id,
                'appointment_date' => $data['appointment_date'],
                'start_time' => $start,
                'end_time' => $end,
                'visit_type' => $data['visit_type'],
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'status' => AppointmentStatus::Scheduled,
                'token_number' => $this->nextToken($doctor->clinic->branch_id, $data['appointment_date']),
                'created_by' => $by->id,
            ]);
        });
    }

    public function reschedule(Appointment $a, string $date, string $time): Appointment
    {
        return DB::transaction(function () use ($a, $date, $time) {
            $doctor = Doctor::query()->lockForUpdate()->findOrFail($a->doctor_id);
            if (! in_array($a->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['action' => __('clinic.errors.bad_transition')]);
            }

            [$start, $end] = $this->window($doctor, $time);
            $this->assertBookable($doctor, $date, $start, $end);
            $this->guard($doctor->id, $date, $start, $end, $a->id);
            $a->update(['appointment_date' => $date, 'start_time' => $start, 'end_time' => $end]);

            return $a;
        });
    }

    public function transition(Appointment $a, AppointmentStatus $to, ?string $reason = null): Appointment
    {
        if (! $a->status->canMoveTo($to)) {
            throw ValidationException::withMessages(['action' => __('clinic.errors.bad_transition')]);
        }

        $stamps = match ($to) {
            AppointmentStatus::CheckedIn => ['checked_in_at' => now()],
            AppointmentStatus::InProgress => ['started_at' => now()],
            AppointmentStatus::Completed => ['completed_at' => now()],
            AppointmentStatus::Cancelled => ['cancel_reason' => $reason],
            default => [],
        };
        $a->update(['status' => $to] + $stamps);

        return $a;
    }

    /** Today's queue (eager-loaded -> no N+1) filtered by branch / doctor / status. */
    public function board(?int $branchId, string $date, array $filters = []): Collection
    {
        return Appointment::query()
            ->with(['patient', 'doctor.user', 'clinic'])
            ->forBranch($branchId)
            ->onDate($date)
            ->when($filters['doctor_id'] ?? null, fn ($q, $v) => $q->where('doctor_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('start_time')
            ->get();
    }

    /** Server-side truth: inside the doctor's working hours and not in the past (never trust the slot grid shown in the browser). */
    private function assertBookable(Doctor $doctor, string $date, string $start, string $end): void
    {
        $h = $doctor->working_hours ?: config('clinic.working_hours');
        if ($start < $h['start'].':00' || $end > $h['end'].':00') {
            throw ValidationException::withMessages(['start_time' => __('clinic.errors.outside_hours')]);
        }
        if (Carbon::parse("$date $start")->isPast()) {
            throw ValidationException::withMessages(['start_time' => __('clinic.errors.slot_past')]);
        }
    }

    private function window(Doctor $doctor, string $time): array
    {
        $start = Carbon::parse($time);
        $step = $doctor->slot_minutes ?: config('clinic.default_slot_minutes');

        return [$start->format('H:i:s'), $start->copy()->addMinutes($step)->format('H:i:s')];
    }

    private function guard(int $doctorId, string $date, string $start, string $end, ?int $ignore = null): void
    {
        if ($this->hasConflict($doctorId, $date, $start, $end, $ignore)) {
            throw ValidationException::withMessages(['start_time' => __('clinic.errors.slot_taken')]);
        }
    }

    private function nextToken(int $branchId, string $date): int
    {
        return (int) Appointment::query()->withTrashed()
            ->where('branch_id', $branchId)->whereDate('appointment_date', $date)->max('token_number') + 1;
    }
}

/*
SYSTEM DESIGN:
Domain service (Rule 3/4): all booking rules live here, not in controllers/components. Booking runs inside DB::transaction with a pessimistic lock on the doctor row (lockForUpdate) so two receptionists can't take the same slot. Status changes go through the FSM in AppointmentStatus.

DATA STRUCTURE:
slotMap: associative array/hash-map 'HH:MM' => bool (Rule 15). Overlap predicate [s1,e1) vs [s2,e2): s1<e2 AND e1>s2. Queue token = MAX(token)+1 per (branch, day). board(): eager loading (with) + indexed range scan.
*/
