<?php
namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Branch;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesClinic;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use MakesClinic, RefreshDatabase;

    private function payload($doctor, $patient, string $time = '10:00'): array
    {
        return ['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => $time, 'visit_type' => 'consultation'];
    }

    public function test_booking_succeeds_and_assigns_token(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $rec = $this->makeStaff('reception', $b);

        $a = app(AppointmentService::class)->book($this->payload($doc, $this->makePatient()), $rec);

        $this->assertSame(1, $a->token_number);
        $this->assertSame(AppointmentStatus::Scheduled, $a->status);
    }

    public function test_overlapping_booking_is_rejected_with_arabic_message(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $rec = $this->makeStaff('reception', $b);
        $svc = app(AppointmentService::class);
        $svc->book($this->payload($doc, $this->makePatient()), $rec);

        try {
            $svc->book($this->payload($doc, $this->makePatient(), '10:10'), $rec);   // overlaps 10:00-10:20
            $this->fail('Expected conflict');
        } catch (ValidationException $e) {
            $this->assertSame(__('clinic.errors.slot_taken'), $e->errors()['start_time'][0]);
        }
    }

    public function test_cancelled_slot_can_be_rebooked(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $doc = $this->makeDoctor($b);
        $rec = $this->makeStaff('reception', $b);
        $svc = app(AppointmentService::class);
        $a = $svc->book($this->payload($doc, $this->makePatient()), $rec);
        $svc->transition($a, AppointmentStatus::Cancelled);

        $this->assertNotNull($svc->book($this->payload($doc, $this->makePatient()), $rec));
    }

    public function test_illegal_status_transition_is_blocked(): void
    {
        $b = Branch::create(['name_ar' => 'ف', 'name_en' => 'B']);
        $a = $this->makeAppointment($this->makeDoctor($b), $this->makePatient(), AppointmentStatus::Completed);

        $this->expectException(ValidationException::class);
        app(AppointmentService::class)->transition($a, AppointmentStatus::Scheduled);
    }
}

/*
SYSTEM DESIGN:
Critical-path feature tests (Rule 43/44): conflict prevention, token numbering, slot release after cancel, FSM guard.

DATA STRUCTURE:

*/
