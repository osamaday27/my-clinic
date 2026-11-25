<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('clinic:send-reminders')->everyTenMinutes()->withoutOverlapping();
Schedule::command('clinic:backup')->cron('0 */6 * * *')->onOneServer();
Schedule::command('clinic:commissions')->monthlyOn(1, '06:00');
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping();

/*
SYSTEM DESIGN:
Task scheduling via cron `* * * * * php artisan schedule:run`. In production run a real worker (Supervisor) instead of the last line.

DATA STRUCTURE:
Cron expression table.
*/
