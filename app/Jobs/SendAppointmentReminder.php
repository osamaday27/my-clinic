<?php
namespace App\Jobs;

use App\Contracts\SmsGateway;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(public Appointment $appointment) {}

    public function handle(SmsGateway $sms): void
    {
        $a = $this->appointment->fresh(['patient', 'doctor.user']);
        if (! $a || $a->reminder_sent_at || ! in_array($a->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
            return;
        }

        $sms->send($a->patient->phone, __('clinic.reminder', [
            'name' => $a->patient->name, 'doctor' => $a->doctor->name,
            'date' => $a->appointment_date->format('Y-m-d'), 'time' => $a->time_label,
        ]));
        $a->update(['reminder_sent_at' => now()]);
    }
}

/*
SYSTEM DESIGN:
Background job on the queue (Redis in production) - network I/O never blocks the web request (Rule 5). Idempotent: re-checks reminder_sent_at and status, retries with back-off.

DATA STRUCTURE:
FIFO queue (Redis list). Payload = serialized model id only (SerializesModels) so PHI is not stored in the queue.
*/
