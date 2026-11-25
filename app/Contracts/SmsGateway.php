<?php
namespace App\Contracts;

interface SmsGateway
{
    public function send(string $phone, string $message): void;
}

/*
SYSTEM DESIGN:
Dependency Inversion (SOLID-D): reminders depend on an interface; the real provider (Twilio, Vonage...) is swapped in the container without touching business code.

DATA STRUCTURE:
Interface / port.
*/
