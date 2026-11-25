<?php

return [
    'name' => env('CLINIC_NAME', 'Clinic & Medical Center ERP'),
    'default_slot_minutes' => 20,
    'working_hours' => ['start' => '09:00', 'end' => '17:00'],
    'reminder_minutes_before' => 120,
    'kpi_cache_seconds' => 60,
    'fees' => ['consultation' => 300, 'follow_up' => 150, 'lab_procedure' => 200],
    'insurance_coverage_percent' => 50,
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
