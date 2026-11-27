<?php

return [
    'name' => env('CLINIC_NAME', 'Clinic & Medical Center ERP'),
    'default_slot_minutes' => 20,
    'working_hours' => ['start' => '09:00', 'end' => '17:00'],
    'reminder_minutes_before' => 120,
    'kpi_cache_seconds' => 60,
    'fees' => ['consultation' => 300, 'follow_up' => 150, 'lab_procedure' => 200],
    'insurance_coverage_percent' => 50,
    'two_factor' => [
        'required_roles' => array_values(array_filter(array_map('trim', explode(',', env('CLINIC_2FA_REQUIRED_ROLES', ''))))),
        'issuer' => 'Clinic ERP',
        'challenge_ttl' => 300,
    ],
    'portal' => ['max_active' => 3, 'max_per_day' => 5, 'cancel_before_hours' => 2],
    'reminders' => ['channel' => env('CLINIC_REMINDER_CHANNEL', 'log')],
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_id' => env('WHATSAPP_PHONE_ID'),
        'template' => env('WHATSAPP_TEMPLATE', 'appointment_reminder'),
        'language' => env('WHATSAPP_LANGUAGE', 'ar'),
        'api_version' => 'v20.0',
        'country_code' => env('WHATSAPP_COUNTRY_CODE', '20'),
    ],
    'lab' => ['disk' => 'local', 'max_kb' => 10240, 'mimes' => ['pdf', 'jpg', 'jpeg', 'png']],
    'expiry_warning_days' => 30,
    'session_idle_minutes' => 30,
    'backup' => [
        'every_hours' => 6,
        'disk' => 'local',
        'directory' => 'backups',
        'keep' => 28,
    ],
];

/*
SYSTEM DESIGN:
Central configuration (Rule: no hard-coded values; read via config() only so config:cache works).

DATA STRUCTURE:
Nested associative array (hash map) keyed by feature.
*/
