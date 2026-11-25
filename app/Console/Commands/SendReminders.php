<?php
namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'clinic:send-reminders';
    protected $description = 'Queue SMS reminders for appointments starting soon';

    public function handle(): int
    {
        $until = now()->addMinutes(config('clinic.reminder_minutes_before'));

        Appointment::query()
            ->whereDate('appointment_date', now()->toDateString())
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value])
            ->whereNull('reminder_sent_at')
            ->whereTime('start_time', '<=', $until->format('H:i:s'))
            ->whereTime('start_time', '>', now()->format('H:i:s'))
            ->chunkById(200, fn ($batch) => $batch->each(fn ($a) => SendAppointmentReminder::dispatch($a)));

        return self::SUCCESS;
    }
}

/*
SYSTEM DESIGN:
Scheduler entry point (every 10 minutes). Uses chunkById(200) - constant memory regardless of volume (Rule 16).

DATA STRUCTURE:
Streaming in chunks of 200 rows keyed by primary key.
*/
