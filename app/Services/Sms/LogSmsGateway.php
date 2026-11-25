<?php
namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

class LogSmsGateway implements SmsGateway
{
    public function send(string $phone, string $message): void
    {
        // Phone is masked in logs. Provider keys live in .env -> config('services.sms') (Rule 45).
        Log::channel('single')->info('SMS', ['to' => substr($phone, 0, 3).'*****'.substr($phone, -2), 'body' => $message]);
    }
}

/*
SYSTEM DESIGN:
Default adapter used in local/dev. Replace with a real adapter that reads credentials from config('services.sms.*').

DATA STRUCTURE:
Adapter pattern.
*/
